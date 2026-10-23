<script setup>
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'

defineProps({
    invoice: Object,
    items: Array,
    payments: Array,
})

const page = usePage()

const form = useForm({
    payment_id: '',
    amount: '',
})

const printInvoice = () => window.print()
</script>

<template>
    <Head :title="invoice.invoice_no" />
    <WorkspaceLayout :title="invoice.invoice_no">
        <div class="panel-head invoice-actions">
            <Link
                href="/invoices"
                class="link"
            >
                All invoices
            </Link>
            <button
                type="button"
                class="btn secondary sm"
                @click="printInvoice"
            >
                Print invoice
            </button>
        </div>
        <article class="panel">
            <div class="panel-head">
                <div>
                    <h3>{{ invoice.invoice_no }}</h3>
                    <p>{{ invoice.period_from }} — {{ invoice.period_to }}</p>
                </div>
                <span>Customer #{{ invoice.customer_id }}</span>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Actual quantity</th>
                            <th>Historical rate</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items"
                            :key="item.id"
                        >
                            <td>{{ item.name }}</td>
                            <td>{{ item.quantity }}</td>
                            <td>₹{{ item.rate }}</td>
                            <td>₹{{ item.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="panel-head">
                <strong>Total ₹{{ invoice.total }}</strong>
                <span>Paid ₹{{ invoice.paid_amount }}</span>
                <strong>Due ₹{{ invoice.due_amount }}</strong>
            </div>
            <p>
                Payments affect this invoice when allocated. Your account ledger includes all
                recorded payments and opening balance.
            </p>
        </article>
        <form
            v-if="page.props.auth?.user?.role === 'client_admin' && Number(invoice.due_amount) > 0"
            class="panel invoice-actions"
            @submit.prevent="form.post(`/invoices/${invoice.id}/allocate`)"
        >
            <div class="panel-head">
                <h3>Allocate an Existing Payment</h3>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="allocation-payment">Payment</label>
                    <select
                        id="allocation-payment"
                        v-model="form.payment_id"
                        required
                    >
                        <option value="">Select payment</option>
                        <option
                            v-for="payment in payments"
                            :key="payment.id"
                            :value="payment.id"
                        >
                            {{ payment.payment_no }} · ₹{{ payment.amount }}
                        </option>
                    </select>
                </div>
                <div class="field">
                    <label for="allocation-amount">Amount to allocate</label>
                    <input
                        id="allocation-amount"
                        v-model="form.amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        required
                    />
                </div>
                <div class="field">
                    <button
                        class="btn"
                        :disabled="form.processing"
                    >
                        Allocate payment
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
    </WorkspaceLayout>
</template>
<style>
@media print {
    .sidebar,
    .topbar,
    nav,
    .invoice-actions {
        display: none !important;
    }
    .main,
    .content,
    .content-inner {
        padding: 0;
        overflow: visible;
    }
    .panel {
        box-shadow: none;
    }
}
</style>
