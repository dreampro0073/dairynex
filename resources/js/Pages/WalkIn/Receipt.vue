<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({ sale: Object })
</script>

<template>
    <Head :title="`Receipt ${sale.receipt_no}`" />
    <AdminLayout>
        <template #title>Sale receipt</template>
        <section class="panel receipt">
            <div class="panel-head">
                <div>
                    <h2>{{ sale.receipt_no }}</h2>
                    <p>{{ sale.sold_at }}</p>
                </div>
                <button
                    class="btn secondary"
                    onclick="window.print()"
                >
                    Print receipt
                </button>
            </div>
            <p>
                <strong>{{ sale.customer_name || 'Guest' }}</strong>
                {{ sale.customer_mobile }}
            </p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="i in sale.items"
                            :key="i.id"
                        >
                            <td>{{ i.product_name }}</td>
                            <td>{{ i.quantity }}</td>
                            <td>₹{{ i.rate }}</td>
                            <td>₹{{ i.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="walkin-total">
                <span>Total · {{ sale.payment_method }}</span>
                <strong>₹{{ sale.total }}</strong>
            </div>
            <p>Paid ₹{{ sale.paid_amount }} · Due ₹{{ sale.due_amount }}</p>
            <Link
                href="/quick-sale"
                class="btn"
            >
                New sale
            </Link>
            <Link
                href="/walk-in-sales"
                class="btn secondary"
            >
                Sales register
            </Link>
        </section>
    </AdminLayout>
</template>
