<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            today_delivered: 0,
            today_total: 0,
            delivery_percentage: 0,

            active_customers: 0,
            route_count: 0,

            collected_today: 0,
            cash_today: 0,
            upi_today: 0,

            outstanding: 0,
            overdue_customers: 0,
        }),
    },

    deliveries: {
        type: Array,
        default: () => [],
    },

    ledgerActivities: {
        type: Array,
        default: () => [],
    },

    productSales: {
        type: Array,
        default: () => [],
    },

    paymentModes: {
        type: Array,
        default: () => [],
    },
})

const money = (value) => {
    return Number(value || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
}

const shortMoney = (value) => {
    const amount = Number(value || 0)

    if (amount >= 100000) {
        return `₹${(amount / 100000).toFixed(1)}L`
    }

    if (amount >= 1000) {
        return `₹${(amount / 1000).toFixed(1)}k`
    }

    return `₹${money(amount)}`
}

const deliveryStatusClass = status => ({ 1: 'pending', 2: 'pending', 3: 'ok', 4: 'due', 5: 'neutral', 6: 'due', 7: 'due', 8: 'pending' }[Number(status)] ?? 'neutral')
const deliveryStatusText = status => ({ 1: 'Pending', 2: 'PIN verified', 3: 'Delivered', 4: 'Skipped', 5: 'Cancelled', 6: 'Failed', 7: 'Customer unavailable', 8: 'Partial' }[Number(status)] ?? 'Unknown')
</script>

<template>
    <AdminLayout>
        <template #title>
            Dashboard
        </template>

        <template #subtitle>
            Daily business overview
        </template>

        <!-- Top Stats -->
        <div class="stat-row">

            <!-- Today's Deliveries -->
            <div class="stat">
                <div class="label">
                    Today's Deliveries
                </div>

                <div class="value">
                    {{ stats.today_delivered }}
                    /
                    {{ stats.today_total }}
                </div>

                <div class="sub up">
                    {{ stats.delivery_percentage }}% complete
                </div>
            </div>

            <!-- Active Customers -->
            <div class="stat blue">
                <div class="label">
                    Active Customers
                </div>

                <div class="value">
                    {{
                        Number(
                            stats.active_customers || 0
                        ).toLocaleString('en-IN')
                    }}
                </div>

                <div class="sub">
                    across {{ stats.route_count }} routes
                </div>
            </div>

            <!-- Collected Today -->
            <div class="stat butter">
                <div class="label">
                    Collected Today
                </div>

                <div class="value">
                    ₹{{ money(stats.collected_today) }}
                </div>

                <div class="sub">
                    Cash {{ shortMoney(stats.cash_today) }}
                    ·
                    UPI {{ shortMoney(stats.upi_today) }}
                </div>
            </div>

            <!-- Outstanding -->
            <div class="stat danger">
                <div class="label">
                    Outstanding / उधार
                </div>

                <div class="value">
                    ₹{{ money(stats.outstanding) }}
                </div>

                <div class="sub down">
                    {{ stats.overdue_customers }}
                    customers overdue
                </div>
            </div>
        </div>

        <!-- Middle Row -->
        <div class="grid-2">

            <!-- Today's Deliveries -->
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        Today's Deliveries — Route wise
                    </h3>

                    <Link
                        href="/deliveries"
                        class="link"
                    >
                        View all
                    </Link>
                </div>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Product</th>
                                <th>Status</th>

                                <th class="num">
                                    Amount
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="delivery in deliveries"
                                :key="delivery.id"
                            >
                                <td>
                                    {{ delivery.customer }}
                                </td>

                                <td>
                                    <span class="route-tag">
                                        {{ delivery.route }}
                                    </span>
                                </td>

                                <td>
                                    {{ delivery.product }}
                                </td>

                                <td>
                                    <span
                                        class="badge"
                                        :class="
                                            deliveryStatusClass(
                                                delivery.status
                                            )
                                        "
                                    >
                                        {{
                                            deliveryStatusText(
                                                delivery.status
                                            )
                                        }}
                                    </span>
                                </td>

                                <td class="num">
                                    ₹{{ money(delivery.amount) }}
                                </td>
                            </tr>

                            <tr v-if="deliveries.length === 0">
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="ic">
                                            <i
                                                class="bi bi-truck"
                                            ></i>
                                        </div>

                                        <div class="t">
                                            No deliveries today
                                        </div>

                                        <div class="s">
                                            Today's deliveries will appear here.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Ledger -->
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        Recent Ledger Activity
                    </h3>

                    <Link
                        href="/ledger"
                        class="link"
                    >
                        Open ledger
                    </Link>
                </div>

                <div class="ledger-list">
                    <div
                        v-for="activity in ledgerActivities"
                        :key="activity.id"
                        class="ledger-item"
                    >
                        <div>
                            <div class="who">
                                {{ activity.customer }}
                            </div>

                            <div class="when">
                                {{ activity.description }}

                                <template v-if="activity.time">
                                    · {{ activity.time }}
                                </template>
                            </div>
                        </div>

                        <div
                            class="amt"
                            :class="
                                activity.type === 'payment'
                                    ? 'paid'
                                    : 'credit'
                            "
                        >
                            {{
                                activity.type === 'payment'
                                    ? '−'
                                    : '+'
                            }}₹{{ money(activity.amount) }}
                        </div>
                    </div>

                    <div
                        v-if="ledgerActivities.length === 0"
                        class="empty-state"
                    >
                        <div class="ic">
                            <i class="bi bi-journal-text"></i>
                        </div>

                        <div class="t">
                            No recent activity
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="grid-2">

            <!-- Product Wise Sales -->
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        Product-wise Sales — Today
                    </h3>
                </div>

                <div>
                    <div
                        v-for="product in productSales"
                        :key="product.name"
                        class="bar-row"
                    >
                        <div class="bl">
                            {{ product.name }}
                        </div>

                        <div class="bar-track">
                            <div
                                class="bar-fill"
                                :style="{
                                    width:
                                        `${product.percentage}%`
                                }"
                            ></div>
                        </div>

                        <div class="bv">
                            {{ shortMoney(product.amount) }}
                        </div>
                    </div>

                    <div
                        v-if="productSales.length === 0"
                        class="empty-state"
                    >
                        <div class="t">
                            No sales today
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Collection -->
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        Payment Collection Mode — Today
                    </h3>
                </div>

                <div>
                    <div
                        v-for="mode in paymentModes"
                        :key="mode.name"
                        class="bar-row"
                    >
                        <div class="bl">
                            {{ mode.name }}
                        </div>

                        <div class="bar-track">
                            <div
                                class="bar-fill"
                                :style="{
                                    width:
                                        `${mode.percentage}%`
                                }"
                            ></div>
                        </div>

                        <div class="bv">
                            ₹{{ money(mode.amount) }}
                        </div>
                    </div>

                    <div
                        v-if="paymentModes.length === 0"
                        class="empty-state"
                    >
                        <div class="t">
                            No collections today
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>