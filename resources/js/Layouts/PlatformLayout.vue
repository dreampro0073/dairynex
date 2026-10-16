<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import '@/../css/platform.css'
defineProps({ title: String, meta: String })
const page = usePage()
const drawer = ref(false)
const nav = [
    ['/platform/clients', '◧', 'Clients / Dairies'], ['/platform/billing', '₹', 'Subscription & Billing'],
    ['/platform/databases', '◫', 'Client Databases'], ['/platform/plans', '▤', 'Plans'],
    ['/platform/usage', '◔', 'Usage Statistics'], ['/platform/support', '✆', 'Support'], ['/platform/settings', '⚙', 'Settings'],
]
const url = computed(() => page.url.split('?')[0])
const active = path => path === '/platform/clients' ? url.value === '/platform' || url.value.startsWith(path) : url.value.startsWith(path)
watch(url, () => { drawer.value = false })
const initials = computed(() => (page.props.auth?.user?.name || 'SA').split(' ').map(p => p[0]).slice(0, 2).join(''))
</script>
<template>
    <Head :title="title" />
    <div class="shell-wrapper platform-shell"><div class="shell">
        <button v-if="drawer" class="overlay show" aria-label="Close navigation" @click="drawer = false"></button>
        <aside class="sidebar" :class="{ open: drawer }" aria-label="Super Admin navigation">
            <div class="sidebar-brand"><div class="logo">DN</div><div><div class="name">{{ page.props.platform?.platform_name || 'DairyNex' }}</div><div class="sub">Super Admin Console</div></div></div>
            <nav class="nav-group"><template v-for="(item, index) in nav" :key="item[0]">
                <div v-if="index === 5" class="nav-group-label">SYSTEM</div>
                <Link :href="item[0]" class="nav-item" :class="{ current: active(item[0]) }" :aria-current="active(item[0]) ? 'page' : undefined"><span class="ic">{{ item[1] }}</span>{{ item[2] }}</Link>
            </template></nav>
            <div class="platform-signout"><span>{{ page.props.auth?.user?.name }}</span><Link href="/logout" method="post" as="button" class="btn secondary sm">Sign out</Link></div>
        </aside>
        <div class="main"><header class="topbar"><button class="menu-btn" aria-label="Open navigation" :aria-expanded="drawer" @click="drawer = !drawer">☰</button><div><h1 class="title">{{ title }}</h1><div class="meta">{{ meta || 'DairyNex platform management' }}</div></div><div class="topbar-right"><slot name="actions"><Link href="/platform/clients/create" class="btn secondary sm">+ Add Client</Link></slot><div class="avatar" :title="page.props.auth?.user?.name">{{ initials }}</div></div></header>
            <main class="content"><div class="content-inner">
                <div v-if="page.props.flash?.success" class="alert alert-success" role="status">{{ page.props.flash.success }}</div>
                <div v-if="page.props.flash?.error" class="alert alert-danger" role="alert">{{ page.props.flash.error }}</div>
                <div v-if="Object.keys(page.props.errors || {}).length" class="alert alert-danger" role="alert"><div v-for="(error, key) in page.props.errors" :key="key">{{ error }}</div></div>
                <slot />
            </div></main>
        </div>
    </div></div>
</template>
