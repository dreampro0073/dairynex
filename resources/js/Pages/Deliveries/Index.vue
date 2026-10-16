<script setup>
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
defineProps({ deliveries: Object, filters: Object, statuses: Array, today: String })
const page = usePage()
const filter = useForm({ date: page.props.filters.date ?? '', status: page.props.filters.status ?? '', q: page.props.filters.q ?? '' })
const generation = useForm({ date: page.props.today })
const errors = form => Object.values(form.errors).join(' ')
</script>
<template>
    <Head title="Deliveries" />
    <WorkspaceLayout title="Daily Deliveries">
        <form v-if="page.props.auth?.user?.role === 'client_admin'" class="panel" @submit.prevent="generation.post('/deliveries/generate')">
            <div class="panel-head"><h3>Generate scheduled deliveries</h3></div>
            <div class="form-grid"><div class="field"><label for="generate-date">Delivery date</label><input id="generate-date" v-model="generation.date" type="date" required :min="today"></div><div class="field"><button class="btn" :disabled="generation.processing">Generate deliveries</button></div></div>
            <p v-if="errors(generation)" class="alert alert-danger" role="alert">{{ errors(generation) }}</p>
        </form>
        <div class="panel">
            <form class="form-grid" @submit.prevent="filter.get(page.url.split('?')[0], { preserveState: true })">
                <div class="field"><label for="filter-date">Date</label><input id="filter-date" v-model="filter.date" type="date"></div>
                <div class="field"><label for="filter-status">Status</label><select id="filter-status" v-model="filter.status"><option value="">All statuses</option><option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.name }}</option></select></div>
                <div class="field"><label for="filter-q">Customer</label><input id="filter-q" v-model="filter.q" type="search"></div>
                <div class="field"><button class="btn secondary" :disabled="filter.processing">Apply filters</button></div>
            </form>
            <div class="table-scroll"><table><thead><tr><th>Customer</th><th>Date</th><th>Status</th><th>PIN policy</th><th class="num">Actual amount</th><th></th></tr></thead><tbody>
                <tr v-for="delivery in deliveries.data" :key="delivery.id"><td>{{ delivery.customer_name }}</td><td>{{ String(delivery.delivery_date).slice(0,10) }}</td><td><span class="badge" :class="delivery.status_id === 3 ? 'ok' : 'pending'">{{ delivery.status }}</span></td><td>{{ delivery.pin_required === null ? 'Legacy: unknown' : delivery.pin_required ? 'Required' : 'Disabled' }}</td><td class="num">₹{{ delivery.total_amount }}</td><td><Link :href="`/deliveries/${delivery.id}`" class="link">View</Link></td></tr>
                <tr v-if="!deliveries.data.length"><td colspan="6"><div class="empty-state">No deliveries found.</div></td></tr>
            </tbody></table></div>
            <Pagination :data="deliveries" />
        </div>
    </WorkspaceLayout>
</template>
