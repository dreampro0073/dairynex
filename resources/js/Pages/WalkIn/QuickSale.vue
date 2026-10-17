<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
const props=defineProps({products:Array,customers:Array,methods:Array,key:String})
const form=useForm({key:props.key,customer_id:'',customer_name:'',customer_mobile:'',payment_method_id:'1',items:[]})
const search=ref(''); const productSearch=ref(''); const saved=ref(false)
const filteredCustomers=computed(()=>props.customers.filter(c=>!search.value||`${c.name} ${c.mobile||''}`.toLowerCase().includes(search.value.toLowerCase())))
const filteredProducts=computed(()=>props.products.filter(p=>p.name.toLowerCase().includes(productSearch.value.toLowerCase())))
const add=(p)=>{const line=form.items.find(i=>i.product_id===p.id); if(line) line.quantity=Number(line.quantity)+1; else form.items.push({product_id:p.id,name:p.name,rate:Number(p.current_rate),quantity:1})}
const total=computed(()=>form.items.reduce((sum,i)=>sum+(Number(i.quantity)||0)*(Number(i.rate)||0),0))
const save=()=>{if(form.processing||saved.value)return; saved.value=true; form.post('/walk-in-sales',{onError:()=>saved.value=false})}
</script>
<template><Head title="Quick Sale"/><AdminLayout><template #title>Quick Sale</template>
<div class="walkin-grid"><section class="panel"><div class="panel-head"><h3>New counter sale</h3></div>
<label class="field"><span>Find customer by name or mobile (optional)</span><input v-model="search" type="search" placeholder="Search customers" /></label>
<select v-model="form.customer_id" class="walkin-select"><option value="">Guest / anonymous</option><option v-for="c in filteredCustomers" :key="c.id" :value="c.id">{{c.name}} · {{c.mobile||'No mobile'}}</option></select>
<div v-if="!form.customer_id" class="form-grid"><label class="field"><span>Guest name (optional)</span><input v-model="form.customer_name" /></label><label class="field"><span>Mobile (optional)</span><input v-model="form.customer_mobile" inputmode="tel" /></label></div>
<div class="panel-head"><h3>Products</h3><input v-model="productSearch" type="search" placeholder="Search products" /></div>
<div class="walkin-products"><button v-for="p in filteredProducts" :key="p.id" type="button" class="walkin-product" @click="add(p)"><strong>{{p.name}}</strong><span>₹{{Number(p.current_rate).toFixed(2)}} / unit</span><small>Add +</small></button></div>
<p v-if="!filteredProducts.length" class="empty-state">No active products available.</p></section>
<section class="panel"><div class="panel-head"><h3>Cart</h3><span>{{form.items.length}} items</span></div>
<div v-for="(item,i) in form.items" :key="item.product_id" class="walkin-line"><div><strong>{{item.name}}</strong><small>₹{{Number(item.rate).toFixed(2)}} each</small></div><input v-model.number="item.quantity" type="number" min="0.001" step="0.001" aria-label="Quantity"/><button type="button" class="btn secondary sm" @click="form.items.splice(i,1)">Remove</button><b>₹{{(item.quantity*item.rate).toFixed(2)}}</b></div>
<p v-if="!form.items.length" class="empty-state">Add products to start a bill.</p>
<label class="field"><span>Payment</span><select v-model.number="form.payment_method_id"><option v-for="m in methods" :key="m.id" :value="m.id">{{m.name.toUpperCase()}}</option><option :value="0">Credit / Udhaar</option></select></label>
<p v-if="Number(form.payment_method_id)===0" class="alert alert-info">Credit requires selecting an existing customer.</p>
<div class="walkin-total"><span>Total</span><strong>₹{{total.toFixed(2)}}</strong></div>
<p v-if="form.errors.items||form.errors.customer_id||form.errors.payment_method_id" class="alert alert-danger" role="alert">{{form.errors.items||form.errors.customer_id||form.errors.payment_method_id}}</p>
<button class="btn" :disabled="form.processing||saved||!form.items.length" @click="save">{{form.processing?'Saving…':'Save sale'}}</button></section></div>
</AdminLayout></template>
