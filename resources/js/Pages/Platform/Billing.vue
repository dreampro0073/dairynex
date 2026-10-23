<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import PlatformLayout from '@/Layouts/PlatformLayout.vue'
import PlatformStats from '@/Components/PlatformStats.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    invoices: Object,
    filters: Object,
    stats: Object,
})

const filter = useForm({
    search: props.filters.search || '',
    status: props.filters.status || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
})

const recording = ref(null),
    receipt = useForm({ payment_reference: '' })

const exportUrl = computed(
    () =>
        '/platform/billing/export?' +
        new URLSearchParams(
            Object.entries(props.filters).filter(([, v]) => v !== null && v !== ''),
        ).toString(),
)

const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Kolkata' })

const state = (row) => (row.paid_at ? 'Paid' : row.due_date < today ? 'Overdue' : 'Pending')

const recordPayment = (row) => {
    recording.value = row.id
    receipt.reset()
}

const save = () =>
    receipt.post(`/platform/billing/${recording.value}/paid`, {
        onSuccess: () => {
            recording.value = null
            receipt.reset()
        },
    })
</script>

<template>
    <PlatformLayout
        title="Subscription & Billing"
        meta="SaaS invoices and recorded payments"
    >
        <template #actions>
            <a
                :href="exportUrl"
                class="btn secondary sm"
            >
                Export CSV
            </a>
        </template>
        <PlatformStats :stats="stats" />
        <section class="panel">
            <div class="panel-head">
                <h3>Client Invoices</h3>
            </div>
            <form
                class="filters"
                @submit.prevent="filter.get('/platform/billing', { preserveState: true })"
            >
                <label>
                    Client
                    <input
                        v-model="filter.search"
                        placeholder="Search dairy"
                    />
                </label>
                <label>
                    Status
                    <select v-model="filter.status">
                        <option value="">All statuses</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </label>
                <label>
                    Period from
                    <input
                        v-model="filter.from"
                        type="date"
                    />
                </label>
                <label>
                    Period to
                    <input
                        v-model="filter.to"
                        type="date"
                    />
                </label>
                <button class="btn sm">Filter</button>
                <Link
                    href="/platform/billing"
                    class="btn secondary sm"
                >
                    Clear
                </Link>
            </form>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Plan</th>
                            <th>Invoice #</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th class="num">Amount</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in invoices.data"
                            :key="row.id"
                        >
                            <td>
                                <Link
                                    :href="`/platform/clients/${row.client_id}`"
                                    class="link"
                                >
                                    {{ row.client }}
                                </Link>
                            </td>
                            <td>
                                <span class="plan-chip standard">{{ row.plan_name }}</span>
                            </td>
                            <td>SAAS-{{ row.id }}</td>
                            <td>
                                {{ row.period_from }} → {{ row.period_to }}
                                <div class="muted">Due {{ row.due_date }}</div>
                            </td>
                            <td>
                                <span
                                    class="badge"
                                    :class="
                                        row.paid_at
                                            ? 'ok'
                                            : state(row) === 'Overdue'
                                              ? 'due'
                                              : 'pending'
                                    "
                                >
                                    {{ state(row) }}
                                </span>
                            </td>
                            <td class="num">
                                ₹{{
                                    Number(row.amount).toLocaleString('en-IN', {
                                        minimumFractionDigits: 2,
                                    })
                                }}
                            </td>
                            <td>
                                <button
                                    v-if="!row.paid_at"
                                    class="btn secondary sm"
                                    @click="recordPayment(row)"
                                >
                                    Record Payment
                                </button>
                                <span
                                    v-else
                                    class="muted"
                                >
                                    {{ row.payment_reference }}
                                    <br />
                                    {{ row.paid_at }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data.length">
                            <td
                                colspan="7"
                                class="empty"
                            >
                                No SaaS invoices found. Assign or renew a dairy’s subscription to
                                issue an invoice.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="invoices" />
        </section>
        <form
            v-if="recording"
            class="panel section-space"
            @submit.prevent="save"
        >
            <div class="panel-head">
                <h3>Record full payment for SAAS-{{ recording }}</h3>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="reference">Receipt / Bank Reference</label>
                    <input
                        id="reference"
                        v-model="receipt.payment_reference"
                        required
                        maxlength="150"
                    />
                </div>
                <p class="muted">
                    Use this after receiving payment. This records the full invoice amount and does
                    not charge a payment gateway.
                </p>
            </div>
            <div class="form-actions">
                <button
                    class="btn sm"
                    :disabled="receipt.processing"
                >
                    Confirm Payment Received
                </button>
                <button
                    type="button"
                    class="btn secondary sm"
                    @click="recording = null"
                >
                    Cancel
                </button>
            </div>
        </form>
    </PlatformLayout>
</template>
