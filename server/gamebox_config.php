<?php
function gamebox_defaults() {
    return array(
        'site' => array('name'=>'ZO游戏盒子','tagline'=>'让每个人都享受游戏的乐趣','description'=>'专为游戏爱好者打造的高速下载与内容管理平台，轻松浏览、搜索和获取精选单机游戏、模拟器游戏及实用工具。','badge'=>'全新版本已发布','primary'=>'#7c5cff','accent'=>'#19d3ae','logo'=>'','forum_url'=>'/forum.php','copyright'=>'© 2026 ZO游戏盒子 版权所有','notice'=>'网站账号与游戏盒子账号通用，请妥善保管您的账号。'),
        'download' => array('version'=>'v1.1.2','url'=>'/download.html','system'=>'Windows 10 / 11','size'=>'128 MB','button'=>'立即下载'),
        'stats' => array(array('value'=>'2+','label'=>'高速通道'),array('value'=>'千兆','label'=>'峰值速度'),array('value'=>'24h','label'=>'设备换绑')),
        'features' => array(
            array('icon'=>'◈','title'=>'多样化游戏分类','text'=>'动作、FPS、策略、竞速、联机、休闲一网打尽。','enabled'=>1),
            array('icon'=>'↗','title'=>'一键分享好友','text'=>'轻松复制常用网盘链接，和好友分享好游戏。','enabled'=>1),
            array('icon'=>'⌕','title'=>'高效搜索系统','text'=>'快速定位感兴趣的内容，节省浏览时间。','enabled'=>1),
            array('icon'=>'⌁','title'=>'账号与设备管理','text'=>'支持设备绑定与自助换绑，安全又灵活。','enabled'=>1),
            array('icon'=>'⬡','title'=>'自动安装与解压','text'=>'下载完成后自动处理，并创建桌面启动入口。','enabled'=>1),
            array('icon'=>'↻','title'=>'内容动态更新','text'=>'资源与客户端持续更新，始终保持新鲜体验。','enabled'=>1)
        ),
        'categories' => array(
            array('title'=>'单机游戏','text'=>'精选优质单机作品，沉浸体验每一个世界。','color'=>'#6c5ce7','enabled'=>1),
            array('title'=>'模拟器游戏','text'=>'高品质主机与掌机模拟内容，一站式管理。','color'=>'#00b894','enabled'=>1),
            array('title'=>'免费专区','text'=>'精选免费与试玩内容，发现更多独立佳作。','color'=>'#ff7675','enabled'=>1)
        ),
        'tech' => array('高性能技术基础','现代化界面设计','自动安装游戏','内容动态更新','双通道下载','快速浏览体验'),
        'screens' => array(
            array('title'=>'游戏窗口','text'=>'快速浏览最近发布或更新的游戏','image'=>'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1400&q=85','enabled'=>1),
            array('title'=>'详情窗口','text'=>'集中查看介绍、图片、视频和版本信息','image'=>'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1400&q=85','enabled'=>1),
            array('title'=>'下载窗口','text'=>'清楚掌握任务进度与实时下载速度','image'=>'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1400&q=85','enabled'=>1)
        ),
        'links' => array(array('label'=>'官方论坛','url'=>'/forum.php'),array('label'=>'游戏下载','url'=>'/forum.php?gid=1')),
        'sections' => array('features'=>1,'categories'=>1,'tech'=>1,'screens'=>1,'download'=>1)
    );
}
function gamebox_config_path() { return __DIR__.'/data/gamebox_config.json'; }
function gamebox_load() {
    $defaults = gamebox_defaults(); $path = gamebox_config_path();
    if (!is_file($path)) return $defaults;
    $saved = json_decode(file_get_contents($path), true);
    return is_array($saved) ? array_replace_recursive($defaults, $saved) : $defaults;
}
function gamebox_save($data) {
    return file_put_contents(gamebox_config_path(), json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX) !== false;
}
function gamebox_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
