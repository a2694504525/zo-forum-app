import {normalizeLink} from './link.js'
export function openExternal(raw,options={}) {
 const link=normalizeLink(raw);if(!link){uni.showToast({title:'链接无效或类型不受支持',icon:'none'});return false}
 const fallback=()=>uni.setClipboardData({data:link.url,success:()=>uni.showToast({title:'无法打开浏览器，链接已复制',icon:'none'})})
 if(link.route&&!options.browser){uni.navigateTo({url:link.route,fail:fallback});return true}
 // #ifdef APP-PLUS
 const open=()=>{try{plus.runtime.openURL(link.url,fallback)}catch{fallback()}}
 if(typeof plus!=='undefined'&&plus.runtime)open()
 else if(typeof document!=='undefined'){let timer;const ready=()=>{clearTimeout(timer);document.removeEventListener('plusready',ready);open()};document.addEventListener('plusready',ready);timer=setTimeout(()=>{document.removeEventListener('plusready',ready);fallback()},3000)}else fallback()
 // #endif
 // #ifdef H5
 const win=window.open(link.url,'_blank','noopener,noreferrer');if(!win)fallback()
 // #endif
 return true
}
