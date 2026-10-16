<template><view class="screen"><view v-if="error" class="error">{{error}}<button @click="load">重试</button></view><view v-if="profile" class="card"><image :src="profile.avatar" class="avatar"/><view class="heading">{{profile.username}}</view><view class="muted">{{profile.grouptitle}} · {{profile.credits}} 积分</view><view class="stats">{{profile.posts}} 帖子 · {{profile.threads?.page?.total||0}} 公开主题</view><button @click="message">发送私信</button></view><ThreadCard v-for="t in profile?.threads?.list||[]" :key="t.tid" :thread="t"/></view></template>
<script setup>
import { ref } from 'vue'
import { onLoad,onReachBottom } from '@dcloudio/uni-app'
import { request,requireLogin } from '../../api/index.js'
import ThreadCard from '../../components/ThreadCard.vue'
const profile=ref(null),error=ref('');let uid,page=1,busy=false,more=false
async function load(){if(busy)return;busy=true;try{const d=await request('user/profile.php',{uid,page});if(page===1)profile.value=d;else profile.value.threads.list.push(...d.threads.list);more=page<d.threads.page.total_page;page++;error.value=''}catch(e){error.value=e.message}finally{busy=false}}
onReachBottom(()=>{if(more)load()})
function message(){if(requireLogin())uni.navigateTo({url:'/pages/chat/index?uid='+uid})}
onLoad(o=>{uid=Number(o.uid);load()})
</script>
<style scoped>.screen{padding:20px 16px}.avatar{width:72px;height:72px;border-radius:50%}.heading{font-size:28px;font-weight:800;margin:16px 0}.stats{margin:20px 0}</style>
