<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    customers: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            paused: 0,
            overdue: 0,
        }),
    },
})

const customerType = (type) => {
    type = Number(type)

    if (type === 1) return 'Regular'
    if (type === 2) return 'Walk-in'
    if (type === 3) return 'Both'

    return '-'
}

const statusText = (status) => {
    status = Number(status)

    if (status === 1) return 'Active'
    if (status === 2) return 'Paused'

    return 'Inactive'
}

const statusClass = (status) => {
    status = Number(status)

    if (status === 1) return 'ok'
    if (status === 2) return 'pending'

    return 'neutral'
}

const money = (value) => {
    return Number(value || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
}

const deleteCustomer = (customer) => {
    if (!confirm(`Are you sure you want to delete "${customer.name}"?`)) {
        return
    }

    router.delete(`/customers/${customer.id}`, {
        preserveScroll: true,
    })
}
</script>

<template>
    <AdminLayout>
        <template #title>Customers</template>

        <template #subtitle>{{ stats.total }} customers</template>

        <template #header-action>
            <Link
                href="/customers/create"
                class="btn secondary sm"
            >
                <i class="bi bi-plus-lg"></i>
                Add Customer
            </Link>
        </template>

        <!-- Stats -->
        <div class="stat-row">
            <div class="stat">
                <div class="label">Total Customers</div>

                <div class="value">
                    {{ Number(stats.total || 0).toLocaleString('en-IN') }}
                </div>

                <div class="sub">on {{ stats.routes || 0 }} routes</div>
            </div>

            <div class="stat blue">
                <div class="label">Active</div>

                <div class="value">
                    {{ Number(stats.active || 0).toLocaleString('en-IN') }}
                </div>

                <div class="sub up">{{ stats.active_percentage || 0 }}%</div>
            </div>

            <div class="stat butter">
                <div class="label">Paused</div>

                <div class="value">
                    {{ Number(stats.paused || 0).toLocaleString('en-IN') }}
                </div>

                <div class="sub">temporary hold</div>
            </div>

            <div class="stat danger">
                <div class="label">Customers with Balance</div>

                <div class="value">
                    {{ Number(stats.overdue || 0).toLocaleString('en-IN') }}
                </div>

                <div class="sub down">customers</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>All Customers</h3>

                <Link
                    href="/customers/create"
                    class="link"
                >
                    + Add Customer
                </Link>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Route</th>
                            <th>Type</th>
                            <th>Today's Qty</th>
                            <th>Status</th>

                            <th class="num">Outstanding</th>

                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="customer in customers"
                            :key="customer.id"
                        >
                            <td>
                                <div class="client-row-name">
                                    {{ customer.name }}
                                </div>

                                <div
                                    v-if="customer.mobile"
                                    class="muted"
                                >
                                    {{ customer.mobile }}
                                </div>
                            </td>

                            <td>
                                {{ customer.route }}
                            </td>

                            <td>
                                {{ customerType(customer.customer_type) }}
                            </td>

                            <td>
                                {{ customer.today_qty }}
                                <div class="meta">
                                    PIN
                                    {{ customer.delivery_pin_required ? 'enabled' : 'disabled' }}
                                </div>
                            </td>

                            <td>
                                <span
                                    class="badge"
                                    :class="statusClass(customer.status)"
                                >
                                    {{ statusText(customer.status) }}
                                </span>
                            </td>

                            <td class="num">₹{{ money(customer.outstanding) }}</td>

                            <td>
                                <Link
                                    :href="`/customers/${customer.id}`"
                                    class="link"
                                >
                                    View
                                </Link>
                                ·
                                <Link
                                    :href="`/customers/${customer.id}/edit`"
                                    class="link"
                                >
                                    Edit
                                </Link>

                                &nbsp;·&nbsp;

                                <button
                                    type="button"
                                    class="link-action danger"
                                    @click="deleteCustomer(customer)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <tr v-if="customers.length === 0">
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="ic">
                                        <i class="bi bi-people"></i>
                                    </div>

                                    <div class="t">No customers found</div>

                                    <div class="s">Add your first customer.</div>

                                    <Link
                                        href="/customers/create"
                                        class="btn sm"
                                    >
                                        <i class="bi bi-plus-lg"></i>
                                        Add Customer
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
