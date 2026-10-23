<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    products: Array,
    customers: Array,
    methods: Array,
    key: String,
})

const form = useForm({
    key: props.key,
    customer_id: '',
    customer_name: '',
    customer_mobile: '',
    payment_method_id: '1',
    items: [],
})

const search = ref('')

const productSearch = ref('')

const saved = ref(false)

const filteredCustomers = computed(() =>
    props.customers.filter(
        (customer) =>
            !search.value ||
            `${customer.name} ${customer.mobile || ''}`
                .toLowerCase()
                .includes(search.value.toLowerCase()),
    ),
)

const filteredProducts = computed(() =>
    props.products.filter((product) =>
        product.name.toLowerCase().includes(productSearch.value.toLowerCase()),
    ),
)

const addProduct = (product) => {
    const line = form.items.find((item) => item.product_id === product.id)
    if (line) {
        line.quantity = Number(line.quantity) + 1
    } else {
        form.items.push({
            product_id: product.id,
            name: product.name,
            rate: Number(product.current_rate),
            quantity: 1,
        })
    }
}

const total = computed(() =>
    form.items.reduce(
        (sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.rate) || 0),
        0,
    ),
)

const save = () => {
    if (form.processing || saved.value) {
        return
    }

    saved.value = true
    form.post('/walk-in-sales', {
        onFinish: () => {
            if (!form.wasSuccessful) {
                saved.value = false
            }
        },
    })
}
</script>

<template>
    <Head title="Quick Sale" />
    <AdminLayout>
        <template #title>Quick Sale</template>
        <div class="walkin-grid">
            <section class="panel">
                <div class="panel-head">
                    <h3>New counter sale</h3>
                </div>
                <label class="field">
                    <span>Find customer by name or mobile (optional)</span>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search customers"
                    />
                </label>
                <select
                    v-model="form.customer_id"
                    class="walkin-select"
                >
                    <option value="">Guest / anonymous</option>
                    <option
                        v-for="customer in filteredCustomers"
                        :key="customer.id"
                        :value="customer.id"
                    >
                        {{ customer.name }} · {{ customer.mobile || 'No mobile' }}
                    </option>
                </select>
                <div
                    v-if="!form.customer_id"
                    class="form-grid"
                >
                    <label class="field">
                        <span>Guest name (optional)</span>
                        <input v-model="form.customer_name" />
                    </label>
                    <label class="field">
                        <span>Mobile (optional)</span>
                        <input
                            v-model="form.customer_mobile"
                            inputmode="tel"
                        />
                    </label>
                </div>
                <div class="panel-head">
                    <h3>Products</h3>
                    <input
                        v-model="productSearch"
                        type="search"
                        placeholder="Search products"
                    />
                </div>
                <div class="walkin-products">
                    <button
                        v-for="product in filteredProducts"
                        :key="product.id"
                        type="button"
                        class="walkin-product"
                        @click="addProduct(product)"
                    >
                        <strong>{{ product.name }}</strong>
                        <span>₹{{ Number(product.current_rate).toFixed(2) }} / unit</span>
                        <small>Add +</small>
                    </button>
                </div>
                <p
                    v-if="!filteredProducts.length"
                    class="empty-state"
                >
                    No active products available.
                </p>
            </section>
            <section class="panel">
                <div class="panel-head">
                    <h3>Cart</h3>
                    <span>{{ form.items.length }} items</span>
                </div>
                <div
                    v-for="(item, index) in form.items"
                    :key="item.product_id"
                    class="walkin-line"
                >
                    <div>
                        <strong>{{ item.name }}</strong>
                        <small>₹{{ Number(item.rate).toFixed(2) }} each</small>
                    </div>
                    <input
                        v-model.number="item.quantity"
                        type="number"
                        min="0.001"
                        step="0.001"
                        aria-label="Quantity"
                    />
                    <button
                        type="button"
                        class="btn secondary sm"
                        @click="form.items.splice(index, 1)"
                    >
                        Remove
                    </button>
                    <b>₹{{ (item.quantity * item.rate).toFixed(2) }}</b>
                </div>
                <p
                    v-if="!form.items.length"
                    class="empty-state"
                >
                    Add products to start a bill.
                </p>
                <label class="field">
                    <span>Payment</span>
                    <select v-model.number="form.payment_method_id">
                        <option
                            v-for="method in methods"
                            :key="method.id"
                            :value="method.id"
                        >
                            {{ method.name.toUpperCase() }}
                        </option>
                        <option :value="0">Credit / Udhaar</option>
                    </select>
                </label>
                <p
                    v-if="Number(form.payment_method_id) === 0"
                    class="alert alert-info"
                >
                    Credit requires selecting an existing customer.
                </p>
                <div class="walkin-total">
                    <span>Total</span>
                    <strong>₹{{ total.toFixed(2) }}</strong>
                </div>
                <p
                    v-if="
                        form.errors.items ||
                        form.errors.customer_id ||
                        form.errors.payment_method_id
                    "
                    class="alert alert-danger"
                    role="alert"
                >
                    {{
                        form.errors.items ||
                        form.errors.customer_id ||
                        form.errors.payment_method_id
                    }}
                </p>
                <button
                    class="btn"
                    :disabled="form.processing || saved || !form.items.length"
                    @click="save"
                >
                    {{ form.processing ? 'Saving…' : 'Save sale' }}
                </button>
            </section>
        </div>
    </AdminLayout>
</template>
