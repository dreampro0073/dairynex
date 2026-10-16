<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
defineProps({ title: String, metrics: Object, records: Object, notice: String })
const page = usePage()
</script>
<template>
    <Head :title="title" />
    <main class="content">
        <div class="content-inner">
            <header class="panel-head"><div><h3>{{ title }}</h3><span>{{ page.props.auth?.user?.name }}</span></div><Link href="/logout" method="post" as="button" class="btn secondary sm">Sign out</Link></header>
            <div class="stat-row"><div v-for="(value, label) in metrics" :key="label" class="stat"><div class="label">{{ label }}</div><div class="value">{{ value }}</div></div></div>
            <div v-if="records" class="panel">
                <div class="table-scroll"><table><thead><tr><th>Name</th><th>Details</th></tr></thead><tbody><tr v-for="record in records.data" :key="record.id"><td>{{ record.name }}</td><td>{{ record.detail }}</td></tr><tr v-if="!records.data.length"><td colspan="2">No records found.</td></tr></tbody></table></div>
                <div class="panel-head"><Link v-if="records.prev_page_url" :href="records.prev_page_url" class="btn secondary sm">Previous</Link><span>Page {{ records.current_page }} of {{ records.last_page }}</span><Link v-if="records.next_page_url" :href="records.next_page_url" class="btn secondary sm">Next</Link></div>
            </div>
            <p class="alert alert-info" role="status">{{ notice }}</p>
        </div>
    </main>
</template>
