# 手机 App 与后台对接

## 地址与鉴权

原生 App 默认基址为 `https://www.zozse.cn/app_api/`，H5 使用同源 `/app_api/`，来源为 `client/api/index.js`。这与桌面端 `/api/desktop/` 不同，App 使用自己的 token。

`auth/login.php` 接收 POST JSON 或表单，字段 `username`、`password`，可选 `questionid`（默认 0）、`answer`。成功后 `account.php::issue_token()` 返回 `token`、`expires_in`、`user`；token 是 32 字节随机值的 64 位十六进制字符串，有效期 2592000 秒，数据库只保存 SHA-256 哈希。

需要登录的后续请求携带 `Authorization: Bearer <token>`。前端存储键为 `zo-token` 与 `zo-user`。`auth/logout.php` 撤销当前 token；修改/找回密码的实现会撤销该账号 token。不要在公开示例中填写真实凭据。

## 请求与响应

GET 参数来自查询字符串；POST 支持表单，或 `Content-Type: application/json`。上传使用 multipart 字段 `image`。`bootstrap.php` 限制 JSON 请求体不超过 200000 字节。

`respond()` 的统一形状为：

```json
{"code":0,"msg":"ok","data":{}}
```

`data` 的实际类型由端点决定，可能为对象、数组或 null。`respond()` 即使业务错误也设置 HTTP 200，客户端必须检查 JSON `code`，不能只判断 HTTP 状态。

| 业务 code | 实现中的用途 |
| --- | --- |
| 0 | 成功 |
| 401 | 未登录或 token 不可用 |
| 403 | 权限、账号状态或服务配置不满足 |
| 404 | 版块、帖子、用户等不可用或不可访问 |
| 405 | 请求方式不支持 |
| 422 | 参数或业务验证失败 |
| 429 | 频率限制或并发繁忙 |
| 500 | 统一异常处理的服务不可用 |
| 502 | 外部服务调用失败 |

Web 服务器、PHP 致命错误或代理也可能返回非 JSON 和其他 HTTP 状态，客户端应处理网络异常。代码来源为 `bootstrap.php`、`account.php` 及对应端点。

## 核心端点

以下字段直接对应仓库源码，不是独立的新协议。其他端点和全部校验请查看 `server/app_api/`。

| 路径（相对 app_api） | 方法 | 主要输入与说明 |
| --- | --- | --- |
| `app/config.php` | GET | 应用名、站点、version、features、announcement |
| `thread/latest.php` | GET | `page`；`sort=latest/hot/digest`，默认 latest |
| `forum/list.php` | GET | 当前可见版块列表 |
| `forum/detail.php` | GET | `fid`，另支持 thread_list 的 page/sort |
| `thread/detail.php` | GET | `tid`、`page`；返回正文、附件、reply_list、reply_page 等 |
| `search/thread.php` | GET | `keyword`；`type=thread/author/forum`，默认 thread |
| `search/ai.php` | GET/POST | `question`，最长 600 字节；返回 answer、threads |
| `auth/login.php` | POST | username、password，可选 questionid、answer |
| `auth/challenge.php` | GET | 返回 challenge_id、question；挑战有效 180 秒 |
| `auth/register.php` | POST | username、password、email、challenge_id、verify_code |
| `auth/me.php` | GET | 当前账号，需 token |
| `auth/logout.php` | POST | 撤销当前 token |
| `auth/sms_send.php` | POST | tel、action、challenge_id、verify_code；action 为 register/lostpw/binding/Unbundling/login，大小写按代码 |
| `thread/post.php` | POST | fid、subject、content_html、request_id；可选 attach_ids、typeid |
| `thread/reply.php` | POST | tid、content_html、request_id；可选 attach_ids |
| `thread/favorite.php`、`thread/like.php` | GET/POST | GET 列表；POST：tid、action=add/remove；需 token |
| `upload/cdn_image.php` | POST multipart | image；返回 url、attach_id、aid、remote；需 token |
| `notify/pm.php` | GET/POST | uid；GET 可带 page；POST 增加 message；需 token |
| `lottery/service.php` | GET/POST | GET action=config/check/prizes/drawcount；POST action=draw、request_id，需 token |
| `gamebox/list.php` | GET | 游戏盒子配置与帖子列表，依赖站点游戏盒子配置或缺省文件 |

分页由 `bootstrap.php::paging()` 定义：每页 20 条，page 限制为 1–1000，分页对象包含 page、perpage、total、total_page。thread_list 返回 list 和 page；其 latest 排序为发帖时间倒序，hot 限当日最后回复帖子并按浏览量优先。具体规则见 bootstrap.php。

注册可能返回 `pending_review=true`，不能假设每次注册都返回 token。短信登录/注册/绑定/解绑/找回的细节以 `auth/sms_*.php` 和 `sms.php` 为准，不能用数学挑战代替短信验证。

发帖/回复的 request_id 必须匹配 16–64 位字母、数字、下划线或连字符，用于幂等控制；attach_ids 最多 9 项，必须属于当前账号且符合附件状态要求。标题最多 80 个 Unicode 字符并受 240 字节输入上限约束；正文受 150000 字节和 50000 字符限制。上传图片允许 JPEG、PNG、WebP，最多 2000 万像素，大小上限取用户组设置与 8 MiB 较小者。来源为 posting.php、upload/cdn_image.php。

## 示例

公开列表请求：

```http
GET /app_api/thread/latest.php?page=1&sort=latest
```

无帖子时的结构示例（不是线上查询结果）：

```json
{"code":0,"msg":"ok","data":{"list":[],"page":{"page":1,"perpage":20,"total":0,"total_page":0}}}
```

登录请求中的值均为占位符：

```http
POST /app_api/auth/login.php
Content-Type: application/json

{"username":"YOUR_USERNAME","password":"YOUR_PASSWORD","questionid":0,"answer":""}
```

后续请求：

```http
GET /app_api/auth/me.php
Authorization: Bearer YOUR_TOKEN
```

完整联调顺序及插件配置位置见 README。环境中没有 `.env` 配置约定；数据库、短信和七牛参数读取现有论坛/插件配置，AI 参数读取站点 `ai-search/config.php`。不要把生产秘密复制进公共配置样例。
