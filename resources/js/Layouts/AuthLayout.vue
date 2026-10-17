<script setup>
import { usePage } from '@inertiajs/vue3'
defineProps({ title: String })
const page = usePage()
</script>
<template>
    <main class="auth-shell">
        <div class="panel auth-panel">
            <div class="sidebar-brand"><div class="logo">DN</div><div><div class="name">DairyNex</div><div class="sub">Dairy management</div></div></div>
            <div class="panel-head"><h3>{{ title }}</h3></div>
            <div v-if="page.props.flash?.success" class="alert alert-success" role="status">{{ page.props.flash.success }}</div>
            <slot />
        </div>
    </main>
</template>
<style scoped>
.auth-shell { min-height: 100vh; min-height: var(--app-viewport-height, 100dvh); display: grid; place-items: center; padding: 24px 16px; background: var(--bg); }
.auth-panel { width: 100%; max-width: 440px; padding: 24px; }
.auth-panel :deep(.field) { margin-bottom: 16px; }
.auth-panel :deep(.btn) { margin: 8px 0; }
</style>
