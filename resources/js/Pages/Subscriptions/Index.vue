<script setup>
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    subscriptions: Object,
    customers: Array,
    products: Array,
    today: String,
})

const page = usePage()

const isAdmin = computed(() => page.props.auth?.user?.role === 'client_admin')

const creation = useForm({
    customer_id: '',
    start_date: props.today,
    end_date: '',
    days: [1, 2, 3, 4, 5, 6, 7],
    slot: 1,
    items: [{ product_id: '', quantity: '1' }],
})

const selected = ref(null)

const pause = useForm({
    pause_from: '',
    pause_to: '',
    reason: '',
})

const change = useForm({
    product_id: '',
    quantity: '1',
    effective_from: '',
    reason: '',
})

const errorText = (form) => Object.values(form.errors).join(' ')
</script>

<template>
    <Head title="Subscriptions" />
    <WorkspaceLayout title="Subscriptions">
        <div class="panel-head">
            <Link
                href="/quantity-change-requests"
                class="link"
            >
                Quantity change requests
            </Link>
        </div>
        <form
            v-if="isAdmin"
            class="panel"
            @submit.prevent="
                creation.post('/subscriptions', {
                    onSuccess: () => creation.reset('customer_id', 'items'),
                })
            "
        >
            <div class="panel-head">
                <h3>New Subscription</h3>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="sub-customer">Customer</label>
                    <select
                        id="sub-customer"
                        v-model="creation.customer_id"
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
                    <label for="sub-start">Start date</label>
                    <input
                        id="sub-start"
                        v-model="creation.start_date"
                        type="date"
                        :min="today"
                        required
                    />
                </div>
                <div class="field">
                    <label for="sub-end">End date (optional)</label>
                    <input
                        id="sub-end"
                        v-model="creation.end_date"
                        type="date"
                        :min="creation.start_date"
                    />
                </div>
                <div class="field">
                    <label for="sub-slot">Supply slot</label>
                    <select
                        id="sub-slot"
                        v-model="creation.slot"
                    >
                        <option :value="1">Morning</option>
                        <option :value="2">Evening</option>
                        <option :value="3">Morning and evening</option>
                    </select>
                </div>
                <fieldset class="field">
                    <legend>Delivery days</legend>
                    <label
                        v-for="(day, index) in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']"
                        :key="day"
                    >
                        <input
                            v-model="creation.days"
                            type="checkbox"
                            :value="index + 1"
                        />
                        {{ day }}
                    </label>
                </fieldset>
            </div>
            <div
                v-for="(item, index) in creation.items"
                :key="index"
                class="form-grid"
            >
                <div class="field">
                    <label :for="`sub-product-${index}`">Product</label>
                    <select
                        :id="`sub-product-${index}`"
                        v-model="item.product_id"
                        required
                    >
                        <option value="">Select product</option>
                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="product.id"
                        >
                            {{ product.name }} · ₹{{ product.base_rate }}
                        </option>
                    </select>
                </div>
                <div class="field">
                    <label :for="`sub-quantity-${index}`">Planned quantity per slot</label>
                    <input
                        :id="`sub-quantity-${index}`"
                        v-model="item.quantity"
                        type="number"
                        min="0.001"
                        max="999999.999"
                        step="0.001"
                        required
                    />
                </div>
                <div class="field">
                    <button
                        v-if="creation.items.length > 1"
                        type="button"
                        class="btn secondary sm"
                        @click="creation.items.splice(index, 1)"
                    >
                        Remove
                    </button>
                </div>
            </div>
            <div class="panel-head">
                <button
                    type="button"
                    class="btn secondary sm"
                    @click="creation.items.push({ product_id: '', quantity: '1' })"
                >
                    Add product
                </button>
                <button
                    class="btn"
                    :disabled="creation.processing"
                >
                    Create subscription
                </button>
            </div>
            <p
                v-if="errorText(creation)"
                class="alert alert-danger"
                role="alert"
            >
                {{ errorText(creation) }}
            </p>
        </form>
        <div class="panel">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Subscription</th>
                            <th>Customer</th>
                            <th>Products / base quantity</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="subscription in subscriptions.data"
                            :key="subscription.id"
                        >
                            <td>{{ subscription.subscription_no }}</td>
                            <td>{{ subscription.customer_name }}</td>
                            <td>
                                <div
                                    v-for="item in subscription.items"
                                    :key="item.id"
                                >
                                    {{ item.name }} · {{ item.quantity }} · ₹{{ item.rate }}
                                </div>
                                <small>Approved changes apply from their effective date.</small>
                            </td>
                            <td>
                                {{ subscription.start_date }} —
                                {{ subscription.end_date || 'Ongoing' }}
                            </td>
                            <td>
                                {{
                                    { 1: 'Active', 2: 'Paused', 3: 'Expired', 4: 'Cancelled' }[
                                        subscription.status
                                    ]
                                }}
                            </td>
                            <td>
                                <button
                                    class="btn secondary sm"
                                    @click="selected = subscription"
                                >
                                    Manage
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!subscriptions.data.length">
                            <td colspan="6">
                                <div class="empty-state">No subscriptions found.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="subscriptions" />
        </div>
        <section
            v-if="selected"
            class="panel"
        >
            <div class="panel-head">
                <h3>Manage {{ selected.subscription_no }}</h3>
                <button
                    class="btn secondary sm"
                    @click="selected = null"
                >
                    Close
                </button>
            </div>
            <form @submit.prevent="pause.post(`/subscriptions/${selected.id}/pause`)">
                <div class="form-grid">
                    <div class="field">
                        <label for="pause-from">Pause from</label>
                        <input
                            id="pause-from"
                            v-model="pause.pause_from"
                            type="date"
                            required
                        />
                    </div>
                    <div class="field">
                        <label for="pause-to">Pause to</label>
                        <input
                            id="pause-to"
                            v-model="pause.pause_to"
                            type="date"
                            required
                        />
                    </div>
                    <div class="field">
                        <label for="pause-reason">Reason (optional)</label>
                        <input
                            id="pause-reason"
                            v-model="pause.reason"
                            maxlength="255"
                        />
                    </div>
                    <div class="field">
                        <button
                            class="btn secondary"
                            :disabled="pause.processing"
                        >
                            Schedule pause
                        </button>
                    </div>
                </div>
                <p>Future pauses resume automatically after the end date.</p>
                <p
                    v-if="errorText(pause)"
                    class="alert alert-danger"
                >
                    {{ errorText(pause) }}
                </p>
            </form>
            <form
                v-if="!isAdmin"
                @submit.prevent="change.post(`/subscriptions/${selected.id}/quantity-change`)"
            >
                <div class="panel-head">
                    <h3>Request a permanent quantity change</h3>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="change-product">Product</label>
                        <select
                            id="change-product"
                            v-model="change.product_id"
                            required
                        >
                            <option value="">Select product</option>
                            <option
                                v-for="item in selected.items"
                                :key="item.id"
                                :value="item.product_id"
                            >
                                {{ item.name }}
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="change-quantity">New quantity per slot</label>
                        <input
                            id="change-quantity"
                            v-model="change.quantity"
                            type="number"
                            min="0"
                            max="999999.999"
                            step="0.001"
                            required
                        />
                    </div>
                    <div class="field">
                        <label for="change-date">Effective from</label>
                        <input
                            id="change-date"
                            v-model="change.effective_from"
                            type="date"
                            required
                        />
                    </div>
                    <div class="field">
                        <label for="change-reason">Reason</label>
                        <input
                            id="change-reason"
                            v-model="change.reason"
                            required
                            maxlength="255"
                        />
                    </div>
                    <div class="field">
                        <button
                            class="btn"
                            :disabled="change.processing"
                        >
                            Request change
                        </button>
                    </div>
                </div>
                <p
                    v-if="errorText(change)"
                    class="alert alert-danger"
                >
                    {{ errorText(change) }}
                </p>
            </form>
        </section>
    </WorkspaceLayout>
</template>
