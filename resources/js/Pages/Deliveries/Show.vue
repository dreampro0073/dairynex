<script setup>
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { ref, onUnmounted } from 'vue'
import axios from 'axios'
const props = defineProps({ delivery: Object })
const page = usePage()
const form = useForm({ items: props.delivery.items.map(item => ({ id: item.id, quantity: item.planned_quantity })), pin: '', skip: false })
const issuedPin = ref(''), pinError = ref(''), issuing = ref(false)
let clearPinTimer
const issue = async () => {
    issuing.value = true; issuedPin.value = ''; pinError.value = ''
    try {
        const { data } = await axios.post(`/deliveries/${props.delivery.id}/pin`)
        issuedPin.value = data.pin
        clearTimeout(clearPinTimer)
        clearPinTimer = setTimeout(() => { issuedPin.value = '' }, 120000)
    } catch (error) { pinError.value = Object.values(error.response?.data?.errors ?? {}).flat().join(' ') || 'Unable to issue a PIN. Please try again later.' }
    finally { issuing.value = false }
}
onUnmounted(() => { clearTimeout(clearPinTimer); issuedPin.value = '' })
const complete = skip => {
    form.skip = skip
    form.post(`/deliveries/${props.delivery.id}/complete`, { onFinish: () => form.reset('pin') })
}
</script>
<template>
    <Head :title="`Delivery #${delivery.id}`" />
    <WorkspaceLayout :title="`Delivery #${delivery.id}`">
        <div class="panel">
            <div class="panel-head"><h3>{{ delivery.customer_name }}</h3><span class="badge" :class="delivery.pending ? 'pending' : 'ok'">{{ delivery.status }}</span></div>
            <div class="form-grid"><div class="field"><label>Delivery date</label><span>{{ String(delivery.delivery_date).slice(0,10) }}</span></div><div class="field"><label>PIN Verification</label><span>{{ delivery.pin_required === null ? 'Unknown legacy policy' : delivery.pin_required ? 'Enabled' : 'Disabled' }}</span></div></div>
            <form @submit.prevent="complete(false)">
                <div class="table-scroll"><table><thead><tr><th>Product</th><th>Planned Qty</th><th>Actual Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody><tr v-for="(item, index) in delivery.items" :key="item.id"><td>{{ item.name }}</td><td>{{ item.planned_quantity }}</td><td><input v-if="delivery.pending && page.props.auth?.user?.role === 'delivery_boy'" v-model="form.items[index].quantity" type="number" min="0" max="999999.999" step="0.001" required :aria-label="`Actual quantity for ${item.name}`"><span v-else>{{ item.actual_quantity }}</span></td><td>₹{{ item.rate }}</td><td>₹{{ item.amount }}</td></tr></tbody></table></div>
                <div v-if="delivery.pending && page.props.auth?.user?.role === 'delivery_boy'" class="form-grid">
                    <div v-if="delivery.pin_required" class="field"><label for="pin">Customer delivery PIN</label><input id="pin" v-model="form.pin" type="password" inputmode="numeric" maxlength="6" autocomplete="off"></div>
                    <div class="field"><button class="btn" :disabled="form.processing">Complete delivery</button><button type="button" class="btn secondary" :disabled="form.processing" @click="complete(true)">Skip delivery</button></div>
                </div>
                <p v-if="Object.keys(form.errors).length" class="alert alert-danger" role="alert">{{ Object.values(form.errors).join(' ') }}</p>
            </form>
            <div v-if="delivery.pending && delivery.pin_required && page.props.auth?.user?.role === 'customer'" class="form-grid"><div class="field"><button type="button" class="btn" :disabled="issuing" @click="issue">{{ issuedPin ? 'Issue a replacement PIN' : 'Show my delivery PIN' }}</button><p v-if="issuedPin" role="status">Delivery PIN: <strong>{{ issuedPin }}</strong></p><p>Each request replaces the previous PIN. Share it with your assigned Delivery Boy when receiving the delivery.</p><p v-if="pinError" class="error-text" role="alert">{{ pinError }}</p></div></div>
            <div class="panel-head"><span>Actual total</span><strong>₹{{ delivery.total_amount }}</strong><span v-if="delivery.delivery_pin_verified_at">PIN verified</span><span v-else-if="!delivery.pending && delivery.pin_required === false">Completed without PIN verification</span></div>
        </div>
    </WorkspaceLayout>
</template>
