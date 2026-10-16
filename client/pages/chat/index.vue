<template><view class="screen"><view class="heading">{{user?.username||'私信'}}</view><view v-if="error" class="error">{{error}}<button @click="load">重试</button></view><view v-for="m in list" :key="m.pmid" :class="['card',{self:Number(m.authorid)===Number(account.user?.uid)}]"><view class="muted">{{m.author||m.authorid}}</view><RichPost :content="m.content_html"/></view><view v-if="!list.length&&!busy" class="empty">暂无对话，发送第一条私信</view><view class="composer"><input v-model="text" maxlength="1000" placeholder="输入私信" confirm-type="send" @confirm="send"/><button :disabled="busy" @click="send">发送</button></view></view></template>
<script setup>
import { ref } from 'vue'
import RichPost from '../../components/RichPost.vue'
import { onLoad,onReachBottom } from '@dcloudio/uni-app'
import { request,post,requireLogin } from '../../api/index.js'
import { useAccount } from '../../store/account.js'
const account=useAccount(),list=ref([]),user=ref(null),text=ref(''),busy=ref(false),error=ref('');let uid,page=1,more=false;onReachBottom(()=>{if(more&&!busy.value)load(true)})
async function load(append=false){if(!append)page=1;busy.value=true;try{const d=await request('notify/pm.php',{uid,page});if(append)list.value.push(...d.list);else list.value=d.list;more=d.list.length===20;page++;user.value=d.user;error.value=''}catch(e){error.value=e.message}finally{busy.value=false}}
async function send(){if(busy.value||!text.value.trim())return;busy.value=true;try{await post('notify/pm.php',{uid,message:text.value});text.value='';await load()}catch(e){error.value=e.message}finally{busy.value=false}}
onLoad(o=>{uid=Number(o.uid);if(requireLogin())load()})
</script>
<style scoped>.screen{padding:20px 16px 110px}.heading{font-size:28px;font-weight:800;margin:20px 4px}.message{white-space:pre-wrap;line-height:1.8}.self{border:1px solid #007aff}.composer{position:fixed;bottom:0;left:0;right:0;display:flex;gap:10px;padding:14px 16px calc(env(safe-area-inset-bottom) + 16px);background:var(--bg)}input{flex:1;min-width:0}button{margin:0;font-size:14px}</style>
