<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import PlatformLayout from '@/Layouts/PlatformLayout.vue'
import PlatformStats from '@/Components/PlatformStats.vue'
import Pagination from '@/Components/Pagination.vue'
import LoginDetails from '@/Components/LoginDetails.vue'

const props = defineProps({
    clients: Object,
    filters: Object,
    plans: Array,
    stats: Object,
    total: Number,
    newThisMonth: Number,
    activities: Array,
})

const filter = useForm({
    search: props.filters.search || '',
    status: props.filters.status ?? '',
    plan_id: props.filters.plan_id || '',
})

const loginClient = ref(null)

const search = () => filter.get('/platform/clients', { preserveState: true, replace: true })

const toggle = (client) => {
    if (window.confirm(`${client.status === 1 ? 'Deactivate' : 'Activate'} ${client.name}?`))
        router.put(`/platform/clients/${client.id}/status`, { status: client.status === 1 ? 0 : 1 })
}
</script>

<template>
    <PlatformLayout
        title="Clients"
        :meta="`${total} dairies onboarded · ${newThisMonth} new this month`"
    >
        <PlatformStats :stats="stats" />
        <section class="panel">
            <div class="panel-head">
                <h3>All Clients</h3>
                <Link
                    href="/platform/clients/create"
                    class="link"
                >
                    + Add Client
                </Link>
            </div>
            <form
                class="filters"
                @submit.prevent="search"
            >
                <label>
                    Search
                    <input
                        v-model="filter.search"
                        placeholder="Dairy, city, email or mobile"
                    />
                </label>
                <label>
                    Status
                    <select v-model="filter.status">
                        <option value="">All statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </label>
                <label>
                    Plan
                    <select v-model="filter.plan_id">
                        <option value="">All plans</option>
                        <option
                            v-for="plan in plans"
                            :key="plan.id"
                            :value="plan.id"
                        >
                            {{ plan.name }}
                        </option>
                    </select>
                </label>
                <button
                    class="btn sm"
                    :disabled="filter.processing"
                >
                    Filter
                </button>
                <Link
                    href="/platform/clients"
                    class="btn secondary sm"
                >
                    Clear
                </Link>
            </form>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Dairy / Client</th>
                            <th>Plan</th>
                            <th>Customers</th>
                            <th>Delivery Boys</th>
                            <th>Status</th>
                            <th class="num">Next Billing</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="client in clients.data"
                            :key="client.id"
                        >
                            <td>
                                <Link
                                    :href="`/platform/clients/${client.id}`"
                                    class="client-row-name link"
                                >
                                    {{ client.name }}
                                </Link>
                                <div class="client-row-sub">
                                    {{
                                        [client.city, client.state].filter(Boolean).join(', ') ||
                                        'Location not set'
                                    }}
                                </div>
                            </td>
                            <td>
                                <span class="plan-chip standard">
                                    {{ client.current_subscription?.plan?.name || 'Unassigned' }}
                                </span>
                            </td>
                            <td>{{ client.customers_count }}</td>
                            <td>{{ client.delivery_boys_count }}</td>
                            <td class="nowrap">
                                <span
                                    class="status-dot"
                                    :class="client.status === 1 ? 'active' : 'inactive'"
                                ></span>
                                {{ client.status === 1 ? 'Active' : 'Inactive' }}
                            </td>
                            <td class="num">{{ client.current_subscription?.end_date || '—' }}</td>
                            <td>
                                <div class="action-row">
                                    <Link
                                        :href="`/platform/clients/${client.id}/edit`"
                                        class="btn secondary sm"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        class="btn secondary sm"
                                        @click="loginClient = client.id"
                                    >
                                        View Login Details
                                    </button>
                                    <button
                                        class="btn secondary sm"
                                        @click="toggle(client)"
                                    >
                                        {{ client.status === 1 ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!clients.data.length">
                            <td
                                colspan="7"
                                class="empty"
                            >
                                No dairies match these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="clients" />
        </section>
        <section class="panel section-space">
            <div class="panel-head">
                <h3>Recent Activities</h3>
            </div>
            <div
                v-for="activity in activities"
                :key="activity.id"
                class="ledger-item"
            >
                <div>
                    <div class="who">{{ activity.action.replaceAll('_', ' ') }}</div>
                    <div class="when">
                        {{ activity.client || 'Platform' }} · {{ activity.actor || 'System' }}
                    </div>
                </div>
                <time class="muted">{{ activity.created_at }}</time>
            </div>
            <div
                v-if="!activities.length"
                class="empty"
            >
                No recorded activity yet.
            </div>
        </section>
        <LoginDetails
            v-if="loginClient !== null"
            :client-id="loginClient"
            @close="loginClient = null"
        />
    </PlatformLayout>
</template>
