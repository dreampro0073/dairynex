<script setup>
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'

defineProps({
    invoices: Object,
    customers: Array,
})

const page = usePage()

const form = useForm({
    customer_id: '',
    month: '',
})
</script>

<template>
    <Head title="Monthly Invoices" />
    <WorkspaceLayout title="Monthly Invoices">
        <form
            v-if="page.props.auth?.user?.role === 'client_admin'"
            class="panel"
            @submit.prevent="form.post('/invoices')"
        >
            <div class="panel-head">
                <h3>Issue Monthly Invoice</h3>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="invoice-customer">Customer</label>
                    <select
                        id="invoice-customer"
                        v-model="form.customer_id"
                        required
                    >
                        <option value="">Select customer</option>
                        <option
                            v-for="customer in customers"
                            :key="customer.id"
                            :value="customer.id"
                        >
                            {{ customer.name }}
                        </option>
                    </select>
                </div>
                <div class="field">
                    <label for="invoice-month">Completed month</label>
                    <input
                        id="invoice-month"
                        v-model="form.month"
                        type="month"
                        required
                    />
                </div>
                <div class="field">
                    <button
                        class="btn"
                        :disabled="form.processing"
                    >
                        Issue invoice
                    </button>
                </div>
            </div>
            <p
                v-if="Object.keys(form.errors).length"
                class="alert alert-danger"
                role="alert"
            >
                {{ Object.values(form.errors).join(' ') }}
            </p>
        </form>
        <div class="panel">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Period</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="invoice in invoices.data"
                            :key="invoice.id"
                        >
                            <td>{{ invoice.invoice_no }}</td>
                            <td>{{ invoice.period_from }} — {{ invoice.period_to }}</td>
                            <td>₹{{ invoice.total }}</td>
                            <td>₹{{ invoice.paid_amount }}</td>
                            <td>₹{{ invoice.due_amount }}</td>
                            <td>
                                <Link
                                    :href="`/invoices/${invoice.id}`"
                                    class="link"
                                >
                                    View / Print
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data.length">
                            <td colspan="6">
                                <div class="empty-state">No invoices found.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="invoices" />
        </div>
    </WorkspaceLayout>
</template>
