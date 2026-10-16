const ORIGIN='https://www.zozse.cn'
export function normalizeLink(raw){
 if(typeof raw!=='string')return null
 let url=raw.trim().replace(/&amp;/g,'&');if(!url||/[\x00-\x20\\]/.test(url)||url.startsWith('#'))return null
 if(url.startsWith('//'))url='https:'+url
 else if(/^www\./i.test(url)||/^[a-z0-9.-]+\.[a-z]{2,}(?:[/:?#]|$)/i.test(url))url='https://'+url
 else if(!/^[a-z][a-z0-9+.-]*:/i.test(url))url=ORIGIN+'/'+url.replace(/^\/+|^\.\//g,'')
 const match=url.match(/^(https?):\/\/([^/?#]+)([^?#]*)(?:\?([^#]*))?(?:#.*)?$/i)
 if(!match||match[2].includes('@')||!/^[-a-z0-9.]+(?::\d+)?$/i.test(match[2]))return null
 const host=match[2].split(':')[0].toLowerCase(),params={}
 for(const item of (match[4]||'').split('&')){const [k,...v]=item.split('=');try{params[decodeURIComponent(k)]=decodeURIComponent(v.join('='))}catch{}}
 const site=host==='zozse.cn'||host.endsWith('.zozse.cn')||host==='zoscloud.cn'||host==='www.zoscloud.cn'
 let route=null
 if(site){const tid=params.tid||match[3].match(/\/thread-(\d+)-/)?.[1],fid=params.fid||match[3].match(/\/forum-(\d+)-/)?.[1],uid=params.uid||match[3].match(/\/space-uid-(\d+)/)?.[1]
  if(tid&&/^\d+$/.test(tid))route='/pages/thread/detail?tid='+tid
  else if(fid&&/^\d+$/.test(fid))route='/pages/index/index?fid='+fid
  else if(uid&&/^\d+$/.test(uid))route='/pages/profile/index?uid='+uid
 }
 return {url,host,site,route}
}
