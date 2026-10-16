<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    pinRequiredDefault: { type: Boolean, default: true },
    customer: {
        type: Object,
        default: null,
    },

    routes: {
        type: Array,
        default: () => [],
    },

    products: {
        type: Array,
        default: () => [],
    },
})

const isEdit = computed(
    () => Boolean(props.customer)
)

const existingItems =
    props.customer?.subscription_items?.length
        ? props.customer.subscription_items
        : [
            {
                product_id: '',
                quantity: 1,
                rate: 0,
            },
        ]

const form = useForm({
    name: props.customer?.name ?? '',
    delivery_pin_required: props.customer?.delivery_pin_required ?? props.pinRequiredDefault,
    status: Number(props.customer?.status ?? 1),
    mobile: props.customer?.mobile ?? '',
    email: props.customer?.email ?? '',

    date_of_birth:
        props.customer?.date_of_birth ?? '',

    gender:
        Number(props.customer?.gender ?? 1),

    customer_type:
        Number(
            props.customer?.customer_type ?? 1
        ),

    credit_limit:
        props.customer?.credit_limit ?? '',

    opening_balance:
        props.customer?.opening_balance ?? '',

    address_line1:
        props.customer?.address_line1 ?? '',

    address_line2:
        props.customer?.address_line2 ?? '',

    landmark:
        props.customer?.landmark ?? '',

    city:
        props.customer?.city ?? '',

    state:
        props.customer?.state ?? '',

    pincode:
        props.customer?.pincode ?? '',

    route_id:
        props.customer?.route_id ?? '',

    latitude:
        props.customer?.latitude ?? '',

    longitude:
        props.customer?.longitude ?? '',

    supply_preference:
        Number(
            props.customer?.supply_preference
            ?? 1
        ),

    subscription_items:
        existingItems.map((item) => ({
            id: item.id ?? null,

            product_id:
                item.product_id ?? '',

            quantity:
                Number(item.quantity ?? 1),

            rate:
                Number(item.rate ?? 0),
        })),

    action: 'save',
})

const addProduct = () => {
    form.subscription_items.push({
        product_id: '',
        quantity: 1,
        rate: 0,
    })
}

const removeProduct = (index) => {
    form.subscription_items.splice(
        index,
        1
    )

    if (
        form.subscription_items.length === 0
    ) {
        addProduct()
    }
}

const productChanged = (item) => {
    const product = props.products.find(
        (product) =>
            Number(product.id)
            === Number(item.product_id)
    )

    item.rate = Number(
        product?.base_rate ?? 0
    )
}

const rowAmount = (item) => {
    return (
        Number(item.quantity || 0)
        * Number(item.rate || 0)
    )
}

const dailyTotal = computed(() => {
    return form.subscription_items.reduce(
        (total, item) =>
            total + rowAmount(item),
        0
    )
})

const money = (value) => {
    return Number(value || 0).toLocaleString(
        'en-IN',
        {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }
    )
}

const submit = (withSubscription = false) => {
    form.action = withSubscription
        ? 'save_subscription'
        : 'save'

    if (isEdit.value) {
        form.put(
            `/customers/${props.customer.id}`
        )

        return
    }

    form.post('/customers')
}
</script>

<template>
    <AdminLayout>
        <template #title>
            {{
                isEdit
                    ? 'Edit Customer'
                    : 'Add New Customer'
            }}
        </template>

        <template #subtitle>
            {{
                isEdit
                    ? 'Update customer profile'
                    : 'Create a new customer profile'
            }}
        </template>

        <Link
            href="/customers"
            class="btn secondary sm pill"
            style="margin-bottom: 14px"
        >
            ← Back to Customers
        </Link>

        <form
            @submit.prevent="
                submit(false)
            "
        >
            <!-- Personal Details -->
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        Personal Details
                    </h3>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label>
                            Customer Name
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Rakesh Sharma"
                            :class="{
                                'field-error':
                                    form.errors.name
                            }"
                        >

                        <div
                            v-if="form.errors.name"
                            class="error-text"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="field">
                        <label>
                            Mobile Number
                        </label>

                        <input
                            v-model="form.mobile"
                            type="text"
                            placeholder="+91 00000 00000"
                            :class="{
                                'field-error':
                                    form.errors.mobile
                            }"
                        >

                        <div
                            v-if="form.errors.mobile"
                            class="error-text"
                        >
                            {{ form.errors.mobile }}
                        </div>
                    </div>

                    <div class="field">
                        <label>
                            Email
                        </label>

                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="optional"
                            :class="{
                                'field-error':
                                    form.errors.email
                            }"
                        >

                        <div
                            v-if="form.errors.email"
                            class="error-text"
                        >
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div class="field">
                        <label>
                            Date of Birth
                        </label>

                        <input
                            v-model="
                                form.date_of_birth
                            "
                            type="date"
                        >
                    </div>

                    <div class="field">
                        <label>
                            Gender
                        </label>

                        <select
                            v-model.number="
                                form.gender
                            "
                        >
                            <option :value="1">
                                Male
                            </option>

                            <option :value="2">
                                Female
                            </option>

                            <option :value="3">
                                Other
                            </option>
                        </select>
                    </div>

                    <div class="field">
                        <label>
                            Customer Type
                        </label>

                        <select
                            v-model.number="
                                form.customer_type
                            "
                        >
                            <option :value="1">
                                Regular
                            </option>

                            <option :value="2">
                                Walk-in
                            </option>

                            <option :value="3">
                                Both
                            </option>
                        </select>
                    </div>

                    <div class="field">
                        <label>
                            Credit Limit (₹)
                        </label>

                        <input
                            v-model="
                                form.credit_limit
                            "
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="e.g. 1000"
                        >
                    </div>

                    <div class="field">
                        <label>
                            Opening Balance (₹)
                        </label>

                        <input
                            v-model="
                                form.opening_balance
                            "
                            type="number"
                            step="0.01"
                            placeholder="e.g. 0"
                        >
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        Address
                    </h3>
                </div>

                <div class="form-grid">
                    <div class="field full">
                        <label>
                            Address
                        </label>

                        <input
                            v-model="
                                form.address_line1
                            "
                            type="text"
                            placeholder="House no., street, area"
                            :class="{
                                'field-error':
                                    form.errors
                                        .address_line1
                            }"
                        >

                        <div
                            v-if="
                                form.errors
                                    .address_line1
                            "
                            class="error-text"
                        >
                            {{
                                form.errors
                                    .address_line1
                            }}
                        </div>
                    </div>

                    <div class="field">
                        <label>
                            Landmark
                        </label>

                        <input
                            v-model="
                                form.landmark
                            "
                            type="text"
                            placeholder="e.g. near Ram Mandir"
                        >
                    </div>

                    <div class="field">
                        <label>
                            City
                        </label>

                        <input
                            v-model="form.city"
                            type="text"
                            placeholder="e.g. Haridwar"
                        >
                    </div>

                    <div class="field">
                        <label>
                            State
                        </label>

                        <input
                            v-model="form.state"
                            type="text"
                            placeholder="e.g. Uttarakhand"
                        >
                    </div>

                    <div class="field">
                        <label>
                            Pincode
                        </label>

                        <input
                            v-model="
                                form.pincode
                            "
                            type="text"
                            placeholder="e.g. 249401"
                        >
                    </div>

                    <div class="field">
                        <label>
                            Delivery Route
                        </label>

                        <select
                            v-model="
                                form.route_id
                            "
                        >
                            <option value="">
                                Select route
                            </option>

                            <option
                                v-for="route in routes"
                                :key="route.id"
                                :value="route.id"
                            >
                                {{ route.name }}
                            </option>
                        </select>
                    </div>

                    <div class="field">
                        <label>
                            Latitude (optional)
                        </label>

                        <input
                            v-model="
                                form.latitude
                            "
                            type="number"
                            step="0.0000001"
                            placeholder="e.g. 29.9457"
                        >
                    </div>

                    <div class="field">
                        <label>
                            Longitude (optional)
                        </label>

                        <input
                            v-model="
                                form.longitude
                            "
                            type="number"
                            step="0.0000001"
                            placeholder="e.g. 78.1642"
                        >
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h3>Delivery PIN Verification</h3></div>
                <div class="form-grid">
                    <div class="field">
                        <label for="customer-status">Account status</label>
                        <select id="customer-status" v-model="form.status"><option :value="1">Active</option><option :value="2">Paused</option><option :value="0">Inactive</option></select>
                        <div v-if="form.errors.status" class="error-text">{{ form.errors.status }}</div>
                        <label for="delivery-pin-required">Verification policy for future deliveries</label>
                        <select id="delivery-pin-required" v-model="form.delivery_pin_required">
                            <option :value="true">Enabled — PIN required</option>
                            <option :value="false">Disabled — no PIN required</option>
                        </select>
                        <div v-if="form.errors.delivery_pin_required" class="error-text">{{ form.errors.delivery_pin_required }}</div>
                        <p>Existing deliveries keep their recorded verification policy.</p>
                    </div>
                </div>
            </div>

            <!-- Supply Preference -->
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        Supply Preference
                    </h3>
                </div>

                <div class="seg-group" style="padding:16px 19px;">
                    <div
                        class="seg-option"
                        :class="{
                            active: Number(form.supply_preference) === 1
                        }"
                        @click="form.supply_preference = 1"
                    >
                        <span class="ic">☀️</span>
                        MORNING
                    </div>

                    <div
                        class="seg-option"
                        :class="{
                            active: Number(form.supply_preference) === 2
                        }"
                        @click="form.supply_preference = 2"
                    >
                        <span class="ic">🌙</span>
                        EVENING
                    </div>

                    <div
                        class="seg-option"
                        :class="{
                            active: Number(form.supply_preference) === 3
                        }"
                        @click="form.supply_preference = 3"
                    >
                        <span class="ic">🌗</span>
                        BOTH
                    </div>
                </div>

                <div
                    v-if="form.errors.supply_preference"
                    class="error-text"
                    style="padding: 0 19px 16px;"
                >
                    {{ form.errors.supply_preference }}
                </div>
            </div>

            <!-- Subscription -->
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>
                            Initial Subscription Products
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="link-action"
                        @click="addProduct"
                    >
                        + Add Product
                    </button>
                </div>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>

                                <th class="num">
                                    Qty
                                </th>

                                <th class="num">
                                    Rate
                                </th>

                                <th class="num">
                                    Amount
                                </th>

                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(
                                    item,
                                    index
                                ) in form
                                    .subscription_items"
                                :key="index"
                            >
                                <td>
                                    <select
                                        class="line-item-select"
                                        v-model="
                                            item.product_id
                                        "
                                        @change="
                                            productChanged(
                                                item
                                            )
                                        "
                                    >
                                        <option value="">
                                            Select product
                                        </option>

                                        <option
                                            v-for="
                                                product
                                                in products
                                            "
                                            :key="
                                                product.id
                                            "
                                            :value="
                                                product.id
                                            "
                                        >
                                            {{
                                                product.name
                                            }}
                                        </option>
                                    </select>
                                </td>

                                <td class="num">
                                    <input
                                        class="line-item-qty"
                                        v-model.number="
                                            item.quantity
                                        "
                                        type="number"
                                        min="0.001"
                                        step="0.001"
                                        style="
                                            width: 90px;
                                        "
                                    >
                                </td>

                                <td class="num">
                                    ₹{{
                                        money(
                                            item.rate
                                        )
                                    }}
                                </td>

                                <td class="num">
                                    ₹{{
                                        money(
                                            rowAmount(
                                                item
                                            )
                                        )
                                    }}
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="
                                            link-action
                                            danger
                                        "
                                        @click="
                                            removeProduct(
                                                index
                                            )
                                        "
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    style="
                        text-align: right;
                        padding: 12px 18px;
                        font-weight: 700;
                    "
                >
                    Daily Total:
                    ₹{{ money(dailyTotal) }}
                </div>
            </div>

            <div
                style="
                    display: grid;
                    grid-template-columns:
                        1fr 1fr;
                    gap: 12px;
                "
            >
                <button
                    type="submit"
                    class="btn pill full-btn"
                    :disabled="
                        form.processing
                    "
                >
                    <i
                        class="bi bi-floppy"
                    ></i>

                    {{
                        form.processing
                            ? 'Saving...'
                            : isEdit
                                ? 'Update Customer'
                                : 'Save Customer'
                    }}
                </button>

                <button
                    type="button"
                    class="
                        btn
                        secondary
                        pill
                        full-btn
                    "
                    :disabled="
                        form.processing
                    "
                    @click="
                        submit(true)
                    "
                >
                    <i
                        class="bi bi-floppy"
                    ></i>

                    {{
                        isEdit
                            ? 'Update & Save Subscription'
                            : 'Save & Create Subscription'
                    }}
                </button>
            </div>
        </form>
    </AdminLayout>
</template>