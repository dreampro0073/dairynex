<script setup>
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link } from '@inertiajs/vue3'
defineProps({ notifications: Object })
</script>
<template>
    <Head title="Notifications" />
    <WorkspaceLayout title="Notifications">
        <div class="panel"><div v-for="notification in notifications.data" :key="notification.id" class="panel-head"><div><h3>{{ notification.title }}</h3><p>{{ notification.message }}</p><small>{{ notification.created_at }}</small></div><Link v-if="!notification.is_read" :href="`/notifications/${notification.id}/read`" method="put" as="button" class="btn secondary sm">Mark read</Link><span v-else class="badge ok">Read</span></div><div v-if="!notifications.data.length" class="empty-state">No notifications.</div><Pagination :data="notifications" /></div>
    </WorkspaceLayout>
</template>
