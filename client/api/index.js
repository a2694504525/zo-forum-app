let BASE = 'https://www.zozse.cn/app_api/'
// #ifdef H5
BASE='/app_api/'
// #endif
export function request(path, data = {}, method='GET') {
 return new Promise((resolve, reject) => uni.request({url:BASE+path,data,method,timeout:path==='search/ai.php'?55000:20000,header:{'Content-Type':'application/json',...(uni.getStorageSync('zo-token')?{Authorization:'Bearer '+uni.getStorageSync('zo-token')}:{})},success:r=>{if(r.statusCode===200&&r.data?.code===0)resolve(r.data.data);else{if(r.data?.code===401){uni.removeStorageSync('zo-token');uni.removeStorageSync('zo-user');const pages=getCurrentPages();if(!pages.at(-1)?.route?.includes('auth/'))uni.navigateTo({url:'/pages/auth/index'})}reject(Object.assign(new Error(r.data?.msg||'请求失败，请重试'),{code:r.data?.code}))}},fail:()=>reject(new Error('网络连接失败，请检查网络后重试'))}))
}
export const post=(path,data)=>request(path,data,'POST')
export function requireLogin(){if(!uni.getStorageSync('zo-token')){uni.navigateTo({url:'/pages/auth/index'});return false}return true}
export function uploadImage(filePath){return new Promise((resolve,reject)=>uni.uploadFile({url:BASE+'upload/cdn_image.php',filePath,name:'image',header:{Authorization:'Bearer '+uni.getStorageSync('zo-token')},success:r=>{try{const d=JSON.parse(r.data);d.code===0?resolve(d.data):reject(new Error(d.msg))}catch{reject(new Error('上传响应无效'))}},fail:()=>reject(new Error('图片上传失败'))}))}
