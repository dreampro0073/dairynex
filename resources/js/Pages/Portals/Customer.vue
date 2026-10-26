<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'

defineProps({
    tab: String,
    customer: Object,
    clientName: String,
    stats: Object,
    deliveries: Array,
    todayDelivery: Array,
    invoices: Object,
    subscription: Object,
    items: Array,
    payments: Object,
    purchases: Array,
    address: Object,
})

const page = usePage()

const tabs = [
    ['home', 'Home', 'bi-house'],
    ['bill', 'Bill', 'bi-receipt'],
    ['purchases', 'Purchases', 'bi-basket'],
    ['subscription', 'Subscription', 'bi-arrow-repeat'],
    ['profile', 'Profile', 'bi-person'],
]

const money = (v) => `₹${Number(v || 0).toFixed(2)}`

const preference = (v) =>
    ({ 1: 'Morning', 2: 'Evening', 3: 'Morning & evening' })[v] || 'As scheduled'

const deliverySummary = (delivery) => {
    const slot = delivery.delivery_slot_id === 2 ? 'Evening' : 'Morning'
    let status = 'Scheduled'

    if (delivery.status_id === 3) {
        status = 'Delivered'
    } else if (delivery.status_id === 4) {
        status = 'Skipped'
    }

    return `${slot} · ${status}`
}
</script>

<template>
    <Head title="My dairy" />
    <main class="portal-app customer-app">
        <header class="portal-head">
            <div class="portal-brand">
                <span class="portal-mark">DN</span>
                <div>
                    <strong>{{ clientName }}</strong>
                    <small>Customer account</small>
                </div>
            </div>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="portal-logout"
            >
                Sign out
            </Link>
        </header>
        <div class="portal-content">
            <template v-if="tab === 'home'">
                <p class="portal-eyebrow">YOUR DAIRY, AT A GLANCE</p>
                <h1>Hello, {{ customer.name }}</h1>
                <div class="portal-card today-card">
                    <span class="portal-icon">🥛</span>
                    <div>
                        <strong>Today's delivery</strong>
                        <small v-if="todayDelivery.length">
                            {{ todayDelivery.map(deliverySummary).join(' / ') }}
                        </small>
                        <small v-else>No delivery scheduled today</small>
                    </div>
                </div>
                <div class="portal-stats">
                    <article>
                        <small>This month</small>
                        <strong>{{ stats.monthly_quantity }} L/kg</strong>
                        <span>delivered</span>
                    </article>
                    <article>
                        <small>Monthly bill</small>
                        <strong>{{ money(stats.monthly_bill) }}</strong>
                        <span>this period</span>
                    </article>
                    <article>
                        <small>Outstanding</small>
                        <strong>{{ money(stats.outstanding) }}</strong>
                        <span>account balance</span>
                    </article>
                </div>
                <section class="portal-card">
                    <div class="portal-section-head">
                        <h2>Recent deliveries</h2>
                        <Link href="/customer?tab=bill">View bills</Link>
                    </div>
                    <div
                        v-for="d in deliveries"
                        :key="d.id"
                        class="portal-row"
                    >
                        <span>
                            <b>{{ d.status }}</b>
                            <small>{{ d.date }}</small>
                        </span>
                        <strong>{{ money(d.amount) }}</strong>
                    </div>
                    <p
                        v-if="!deliveries.length"
                        class="portal-muted"
                    >
                        Your delivery history will appear here.
                    </p>
                </section>
            </template>
            <template v-else-if="tab === 'bill'">
                <p class="portal-eyebrow">ACCOUNT SUMMARY</p>
                <h1>Your bills</h1>
                <div class="portal-stats">
                    <article>
                        <small>Current period</small>
                        <strong>{{ money(stats.monthly_bill) }}</strong>
                        <span>bill total</span>
                    </article>
                    <article>
                        <small>Outstanding</small>
                        <strong>{{ money(stats.outstanding) }}</strong>
                        <span>account balance</span>
                    </article>
                </div>
                <section class="portal-card">
                    <h2>Invoice history</h2>
                    <div
                        v-for="i in invoices"
                        :key="i.id"
                        class="portal-row"
                    >
                        <span>
                            <b>{{ i.invoice_no }}</b>
                            <small>{{ i.invoice_date }}</small>
                        </span>
                        <strong>
                            {{ money(i.total) }}
                            <small>Due {{ money(i.due_amount) }}</small>
                        </strong>
                        <Link :href="`/invoices/${i.id}`">Open</Link>
                    </div>
                    <p
                        v-if="!invoices.length"
                        class="portal-muted"
                    >
                        No invoices have been issued yet.
                    </p>
                </section>
                <section class="portal-card">
                    <h2>Payment history</h2>
                    <div
                        v-for="p in payments"
                        :key="p.id"
                        class="portal-row"
                    >
                        <span>
                            <b>{{ p.payment_no }}</b>
                            <small>{{ p.payment_date }}</small>
                        </span>
                        <strong>{{ money(p.amount) }}</strong>
                    </div>
                    <p
                        v-if="!payments.length"
                        class="portal-muted"
                    >
                        No payments recorded.
                    </p>
                </section>
            </template>
            <template v-else-if="tab === 'purchases'">
                <p class="portal-eyebrow">COUNTER PURCHASES</p>
                <h1>Purchase history</h1>
                <section class="portal-card">
                    <div v-for="sale in purchases" :key="sale.id" class="portal-row portal-purchase">
                        <span>
                            <b>{{ sale.items.map(i => `${i.product_name} × ${i.quantity}`).join(', ') }}</b>
                            <small>{{ sale.sold_at }} · {{ sale.receipt_no }}</small>
                        </span>
                        <span>
                            <b>{{ money(sale.total) }}</b>
                            <small>{{ sale.payment_method }} · {{ sale.due_amount > 0 ? `Outstanding ${money(sale.due_amount)}` : 'Paid' }}</small>
                        </span>
                    </div>
                    <p v-if="!purchases.length" class="portal-muted">No counter purchases yet.</p>
                </section>
            </template>
            <template v-else-if="tab === 'subscription'">
                <p class="portal-eyebrow">YOUR REGULAR SUPPLY</p>
                <h1>Subscription</h1>
                <section
                    v-if="subscription"
                    class="portal-card"
                >
                    <div class="portal-section-head">
                        <h2>{{ subscription.subscription_no }}</h2>
                        <span class="portal-badge">
                            {{ subscription.status === 1 ? 'Active' : 'Paused' }}
                        </span>
                    </div>
                    <p>
                        From {{ subscription.start_date }}
                        <span v-if="subscription.end_date">to {{ subscription.end_date }}</span>
                    </p>
                    <div
                        v-for="i in items"
                        :key="i.name"
                        class="portal-row"
                    >
                        <span>
                            <b>{{ i.name }}</b>
                            <small>{{ i.default_quantity }} per delivery</small>
                        </span>
                        <strong>{{ money(i.rate) }}</strong>
                    </div>
                    <p class="portal-muted">Delivery schedule is managed by your dairy.</p>
                    <Link
                        href="/subscriptions"
                        class="portal-action"
                    >
                        Manage subscription
                    </Link>
                </section>
                <section
                    v-else
                    class="portal-card"
                >
                    <p>No active subscription is linked to this account.</p>
                </section>
            </template>
            <template v-else>
                <p class="portal-eyebrow">ACCOUNT SETTINGS</p>
                <h1>Your profile</h1>
                <section class="portal-card">
                    <div class="portal-profile">
                        <span class="portal-avatar">{{ customer.name?.slice(0, 1) }}</span>
                        <div>
                            <h2>{{ customer.name }}</h2>
                            <small>{{ clientName }}</small>
                        </div>
                    </div>
                    <div class="portal-row">
                        <span>Mobile</span>
                        <b>{{ customer.mobile || 'Not set' }}</b>
                    </div>
                    <div class="portal-row">
                        <span>Address</span>
                        <b>{{ address?.address_line1 || 'No address on file' }}</b>
                    </div>
                    <div class="portal-row">
                        <span>Delivery preference</span>
                        <b>{{ preference(customer.supply_preference) }}</b>
                    </div>
                    <Link
                        href="/change-password"
                        class="portal-action"
                    >
                        Change password
                    </Link>
                </section>
            </template>
        </div>
        <nav class="portal-bottom">
            <Link
                v-for="[key, label, icon] in tabs"
                :key="key"
                :href="`/customer?tab=${key}`"
                :class="{ active: tab === key }"
            >
                <i :class="`bi ${icon}`"></i>
                <span>{{ label }}</span>
            </Link>
        </nav>
    </main>
</template>
