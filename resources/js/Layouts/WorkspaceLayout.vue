<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
defineProps({ title: String })
const page = usePage()
</script>
<template>
    <AdminLayout v-if="page.props.auth?.user?.role === 'client_admin'">
        <template #title>{{ title }}</template><slot />
    </AdminLayout>
    <main v-else class="content">
        <div class="content-inner">
            <header class="panel-head"><h3>{{ title }}</h3><div>{{ page.props.auth?.user?.name }} <Link href="/logout" method="post" as="button" class="btn secondary sm">Sign out</Link></div></header>
            <nav class="panel-head"><Link :href="page.props.auth?.user?.role === 'super_admin' ? '/platform' : page.props.auth?.user?.role === 'customer' ? '/customer' : '/delivery'" class="link">{{ page.props.auth?.user?.role === 'super_admin' ? 'Platform overview' : 'My deliveries' }}</Link><Link v-if="page.props.auth?.user?.role === 'customer'" href="/subscriptions" class="link">Subscriptions</Link><Link v-if="page.props.auth?.user?.role !== 'super_admin'" href="/account/ledger" class="link">Ledger / Payments</Link><Link v-if="page.props.auth?.user?.role === 'customer'" href="/invoices" class="link">Invoices</Link><Link href="/notifications" class="link">Notifications</Link></nav>
            <div v-if="page.props.flash?.success" class="alert alert-success" role="status">{{ page.props.flash.success }}</div>
            <slot />
        </div>
    </main>
</template>
