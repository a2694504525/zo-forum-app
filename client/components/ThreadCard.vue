<template><view class="card" hover-class="pressed" @click="open"><view class="muted">{{thread.author}} · {{date}}</view><image v-if="thread.cover&&!failed" :src="thread.cover" lazy-load mode="widthFix" class="cover" @error="failed=true"/><view class="title">{{thread.subject}}</view><view class="muted footer">{{thread.views||0}} 次浏览 · {{thread.replies||0}} 条回复</view></view></template>
<script setup>
import { computed,ref } from 'vue'
const failed=ref(false)
const props=defineProps({thread:Object})
const date=computed(()=>props.thread.dateline?new Date(Number(props.thread.dateline)*1000).toLocaleDateString():'')
function open(){uni.navigateTo({url:'/pages/thread/detail?tid='+props.thread.tid})}
</script>
<style scoped>.cover{width:100%;max-height:240px;border-radius:14px;margin-top:14px}.title{margin:10px 0}.footer{margin-top:14px}.pressed{opacity:.7;transform:scale(.99)}</style>
