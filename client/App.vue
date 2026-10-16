<script>
import { request } from './api/index.js'
import { openExternal } from './utils/openExternal.js'
export default { onLaunch() {
 // #ifdef APP-PLUS
 plus.runtime.getProperty(plus.runtime.appid, async info=>{try{const config=await request('app/config.php');const v=config.version;const parts=x=>String(x).split('.').map(Number);const a=parts(v.latest),b=parts(info.version);const newer=a.some((n,i)=>n>(b[i]||0)&&a.slice(0,i).every((x,j)=>x===(b[j]||0)));if(newer&&v.download_url)uni.showModal({title:'发现新版本 '+v.latest,content:v.changelog||'更新以获得最新功能',showCancel:!v.force_update,confirmText:'下载更新',success:r=>{if(r.confirm)openExternal(v.download_url)}})}catch{}})
 // #endif
} }

</script>
<style>
page{--bg:#f2f2f7;--card:#fff;--ink:#1c1c1e;--muted:#8e8e93;--line:#e5e5ea;background:var(--bg);color:var(--ink);font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Microsoft YaHei',sans-serif;font-size:15px}
@media(prefers-color-scheme:dark){page{--bg:#000;--card:#1c1c1e;--ink:#f2f2f7;--muted:#98989d;--line:#38383a}}
button{border-radius:30px;background:#007aff;color:#fff;font-size:15px}button:after{border:0}.muted{color:var(--muted);font-size:12px}.card{background:var(--card);border-radius:20px;margin:12px 0;padding:20px}.title{font-size:19px;font-weight:650;line-height:1.5}.empty{padding:50px 20px;text-align:center;color:var(--muted)}.error{padding:24px;background:var(--card);border-radius:18px;color:#ff3b30}input{height:46px;padding:0 16px;border-radius:14px;background:var(--card);color:var(--ink)}
</style>
