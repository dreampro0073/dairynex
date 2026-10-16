<script setup>
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
const props = defineProps({ title: String, ledger: Boolean, records: Object, customers: Array, methods: Array, filters: Object, paymentKey: String, selectedBalance: String, openingBalance: [String, Number] })
const page = usePage()
const filters = useForm({ customer_id: props.filters.customer_id ?? '', from: props.filters.from ?? '', to: props.filters.to ?? '' })
const payment = useForm({ customer_id: '', amount: '', payment_method_id: 1, transaction_reference: '', key: props.paymentKey })
const submit = () => payment.post('/payments', { onSuccess: () => { payment.reset('amount', 'transaction_reference'); payment.key = page.props.paymentKey } })
</script>
<template>
    <Head :title="title" />
    <WorkspaceLayout :title="title">
        <div class="panel-head"><Link href="/ledger" class="link">Ledger</Link><Link href="/payments" class="link">Payments</Link></div>
        <form v-if="!ledger && page.props.auth?.user?.role !== 'customer'" class="panel" @submit.prevent="submit">
            <div class="panel-head"><h3>Record Payment</h3></div>
            <div class="form-grid"><div class="field"><label for="payment-customer">Customer</label><select id="payment-customer" v-model="payment.customer_id" required><option value="">Select customer</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select></div><div class="field"><label for="payment-amount">Amount</label><input id="payment-amount" v-model="payment.amount" type="number" min="0.01" max="999999999.99" step="0.01" required></div><div class="field"><label for="payment-method">Method</label><select id="payment-method" v-model="payment.payment_method_id"><option v-for="method in methods" :key="method.id" :value="method.id">{{ method.name }}</option></select></div><div class="field"><label for="payment-reference">Transaction reference (optional)</label><input id="payment-reference" v-model="payment.transaction_reference" maxlength="150"></div><div class="field"><button class="btn" :disabled="payment.processing">Record payment</button></div></div>
            <p v-if="Object.keys(payment.errors).length" class="alert alert-danger" role="alert">{{ Object.values(payment.errors).join(' ') }}</p>
        </form>
        <div class="panel">
            <form class="form-grid" @submit.prevent="filters.get(page.url.split('?')[0], { preserveState: true })"><div class="field"><label for="finance-customer">Customer</label><select id="finance-customer" v-model="filters.customer_id"><option value="">All authorized customers</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select></div><div class="field"><label for="finance-from">From</label><input id="finance-from" v-model="filters.from" type="date"></div><div class="field"><label for="finance-to">To</label><input id="finance-to" v-model="filters.to" type="date"></div><div class="field"><button class="btn secondary" :disabled="filters.processing">Apply filters</button></div></form>
            <div v-if="selectedBalance !== null" class="panel-head"><span>Opening balance ₹{{ openingBalance }}</span><strong>Current balance ₹{{ selectedBalance }}</strong></div>
            <div class="table-scroll"><table><thead><tr><th>Customer ID</th><th>Date</th><th>Reference</th><th v-if="ledger">Debit</th><th v-if="ledger">Credit</th><th v-if="ledger">Balance after entry</th><th v-else>Amount</th></tr></thead><tbody><tr v-for="record in records.data" :key="record.id"><td>{{ record.customer_id }}</td><td>{{ record.transaction_date || record.payment_date }}</td><td>{{ record.description || record.payment_no }}</td><template v-if="ledger"><td>₹{{ record.debit }}</td><td>₹{{ record.credit }}</td><td>₹{{ record.balance }}</td></template><td v-else>₹{{ record.amount }}</td></tr><tr v-if="!records.data.length"><td colspan="6"><div class="empty-state">No transactions found.</div></td></tr></tbody></table></div><Pagination :data="records" />
        </div>
    </WorkspaceLayout>
</template>
