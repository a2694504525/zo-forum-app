<template><view class="rich"><mp-html :content="content" :copy-link="false" :preview-img="true" :lazy-load="true" :tag-style="styles" error-img="/static/image-unavailable.png" @linktap="link" @error="mediaError"/><view v-if="failed" class="muted">部分媒体暂时无法加载，可稍后刷新重试。</view></view></template>
<script setup>
import {ref} from 'vue'
import MpHtml from 'mp-html/dist/uni-app/components/mp-html/mp-html.vue'
import {openExternal} from '../utils/openExternal.js'
defineProps({content:{type:String,default:''}})
const failed=ref(false),styles={img:'max-width:100%;height:auto',video:'width:100%;max-width:100%',audio:'max-width:100%',pre:'background:#1c1c1e;color:#f2f2f7;padding:16px;border-radius:12px;font-family:monospace;white-space:pre-wrap;overflow-wrap:anywhere',blockquote:'border-left:3px solid #007aff;background:#80808012;margin:14px 0;padding:12px 16px',table:'max-width:100%;overflow-x:auto;border-collapse:collapse',td:'border:1px solid #8e8e9360;padding:6px',th:'border:1px solid #8e8e9360;padding:6px',a:'color:#007aff;overflow-wrap:anywhere',p:'line-height:1.8',ol:'padding-left:24px',ul:'padding-left:24px'}
function link(e){if(e.href&&!e.href.startsWith('#'))openExternal(e.href)}
function mediaError(){failed.value=true}
</script>
<style scoped>.rich{max-width:100%;overflow:hidden;overflow-wrap:anywhere}.muted{margin-top:12px}</style>
