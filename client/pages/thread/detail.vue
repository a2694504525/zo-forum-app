<template><view class="screen"><view v-if="error" class="error">{{error}}<button @click="reset">重新加载</button></view><template v-if="thread"><view class="card"><view class="muted author" @click="author(thread.authorid)"><image :src="thread.avatar" class="avatar"/>{{thread.author}} · {{thread.views}} 次浏览</view><view class="title">{{thread.subject}}</view><RichPost :content="thread.content_html"/><view class="actions"><button @click="favorite">{{thread.favorited?'已收藏':'收藏'}}</button><button @click="like">{{thread.liked?'已赞':'点赞'}} {{thread.likes||0}}</button><button @click="share">分享</button></view><view class="muted tools"><text @click="copy">复制链接</text><text @click="report">举报</text></view></view><view class="section">回复 · {{thread.replies}}</view><view v-for="reply in replies" :key="reply.pid" class="card"><view class="muted" @click="author(reply.authorid)">{{reply.author}} ›</view><RichPost :content="reply.content_html"/><view class="muted quote" @click="quote(reply)">引用回复</view></view><view v-if="!replies.length&&!busy" class="empty">暂无公开回复</view><button v-if="more&&!busy" @click="load">加载更多回复</button><view class="replybar"><input v-model="replyText" placeholder="说说你的看法…" maxlength="1000" confirm-type="send" @confirm="send"/><button :disabled="sending" @click="send">回复</button><text @click="compose">✎</text></view></template><view v-if="busy" class="empty">正在加载…</view></view></template>
<script setup>
import { ref } from 'vue'
import { onLoad,onShow,onPullDownRefresh,onReachBottom } from '@dcloudio/uni-app'
import RichPost from '../../components/RichPost.vue'
import { request,post,requireLogin } from '../../api/index.js'
import { openExternal } from '../../utils/openExternal.js'
const thread=ref(null),replies=ref([]),busy=ref(false),sending=ref(false),error=ref(''),more=ref(false),replyText=ref('');let tid=0,page=1,requestId=''
const url=()=> 'https://www.zozse.cn/forum.php?mod=viewthread&tid='+tid
async function load(){if(busy.value)return;busy.value=true;error.value='';try{const d=await request('thread/detail.php',{tid,page});thread.value=d;replies.value.push(...d.reply_list);more.value=page<d.reply_page.total_page;page++}catch(e){error.value=e.message}finally{busy.value=false;uni.stopPullDownRefresh()}}
function reset(){if(busy.value){uni.stopPullDownRefresh();return}page=1;replies.value=[];load()}
function link(e){if(!e.href?.startsWith('#'))openExternal(e.href)}
function author(uid){if(uid)uni.navigateTo({url:'/pages/profile/index?uid='+uid})}
function copy(){uni.setClipboardData({data:url()})}
function report(){const target='https://www.zozse.cn/misc.php?mod=report&rtype=post&rid='+thread.value.pid;openExternal(target,{browser:true})}
function share(){
 // #ifdef APP-PLUS
 if(plus.share?.sendWithSystem){plus.share.sendWithSystem({content:thread.value.subject,href:url()},()=>{},()=>{});return}
 // #endif
 // #ifdef H5
 if(navigator.share){navigator.share({title:thread.value.subject,url:url()}).catch(()=>{});return}
 // #endif
 copy()
}
async function favorite(){if(!requireLogin())return;try{const d=await post('thread/favorite.php',{tid,action:thread.value.favorited?'remove':'add'});thread.value.favorited=d.favorited}catch(e){error.value=e.message}}
async function like(){if(!requireLogin())return;try{const d=await post('thread/like.php',{tid,action:thread.value.liked?'remove':'add'});thread.value.liked=d.liked;thread.value.likes=d.likes}catch(e){error.value=e.message}}
function compose(){if(requireLogin())uni.navigateTo({url:'/pages/compose/index?tid='+tid})}
function quote(r){if(requireLogin()){uni.setStorageSync('zo-quote',{author:r.author,content:r.content_html.replace(/<[^>]*>/g,' ')});compose()}}
async function send(){if(sending.value||!requireLogin()||!replyText.value.trim())return;sending.value=true;requestId=requestId||Date.now().toString(36)+Math.random().toString(36).slice(2)+Math.random().toString(36).slice(2);const escape=s=>s.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));try{const d=await post('thread/reply.php',{tid,content_html:'<p>'+escape(replyText.value)+'</p>',request_id:requestId});requestId='';replyText.value='';uni.showToast({title:d.moderated?'回复待审核':'回复成功',icon:'none'});reset()}catch(e){error.value=e.message}finally{sending.value=false}}
onLoad(o=>{tid=Number(o.tid);reset()});onPullDownRefresh(reset);onReachBottom(()=>{if(more.value)load()})
</script>
<style scoped>.screen{padding:8px 16px 120px}.title{font-size:25px;margin:16px 0 24px}.section{font-size:20px;font-weight:700;margin:30px 4px 12px}.author{display:flex;align-items:center;gap:12px}.avatar{width:36px;height:36px;border-radius:50%}.actions{display:flex;gap:12px;margin-top:24px}.actions button{flex:1;font-size:12px;padding:0 10px}.tools{display:flex;gap:30px;margin-top:20px}.quote{margin-top:16px;color:#007aff}.replybar{position:fixed;bottom:0;left:0;right:0;display:flex;align-items:center;gap:10px;padding:12px 16px calc(env(safe-area-inset-bottom) + 14px);background:var(--card);backdrop-filter:blur(20px);border-top:1px solid var(--line)}.replybar input{flex:1;min-width:0;background:var(--bg)}.replybar button{font-size:13px;padding:0 14px;margin:0}.replybar text{font-size:24px;color:#007aff}</style>
