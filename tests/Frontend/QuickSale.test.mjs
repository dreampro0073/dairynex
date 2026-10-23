import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse } from '@vue/compiler-sfc'
import { computed, ref } from 'vue'

// Run the page's actual setup code with a controllable request lifecycle.
// These tests cover component logic, not browser clicks or real network requests.
const source = readFileSync(
    new URL('../../resources/js/Pages/WalkIn/QuickSale.vue', import.meta.url),
    'utf8',
)
const { descriptor } = parse(source)
const setup = descriptor.scriptSetup.content.replace(/^import .*$/gm, '')

function createSale() {
    const requests = []
    const props = {
        key: 'same-sale-key',
        products: [{ id: 1, name: 'Milk', current_rate: '56.25' }],
        customers: [],
        methods: [],
    }
    const useForm = (data) => ({
        ...data,
        processing: false,
        wasSuccessful: false,
        post(url, options) {
            this.processing = true
            this.wasSuccessful = false
            requests.push({ url, key: this.key, options })
        },
    })
    const createSetup = new Function(
        'defineProps',
        'useForm',
        'computed',
        'ref',
        `${setup}\nreturn { form, addProduct, save }`,
    )
    const page = createSetup(() => props, useForm, computed, ref)
    page.addProduct(props.products[0])

    return {
        ...page,
        requests,
        finish(success = false) {
            page.form.processing = false
            page.form.wasSuccessful = success
            requests.at(-1).options.onFinish?.()
        },
    }
}

test('ten rapid Save clicks submit one request and keep the same sale key', () => {
    const page = createSale()

    for (let click = 0; click < 10; click++) {
        page.save()
    }

    assert.equal(page.requests.length, 1)
    assert.equal(page.requests[0].url, '/walk-in-sales')
    assert.equal(page.requests[0].key, 'same-sale-key')
})

test('a network failure allows retry with the original key', () => {
    const page = createSale()
    page.save()
    page.finish()
    page.save()

    assert.equal(page.requests.length, 2)
    assert.equal(page.requests[0].key, page.requests[1].key)
    assert.equal(page.form.items.length, 1)
})

for (const event of ['onError', 'onCancel', 'onHttpException']) {
    test(`${event} allows retry without losing the cart`, () => {
        const page = createSale()
        page.save()
        page.requests[0].options[event]?.({})
        page.finish()
        page.save()

        assert.equal(page.requests.length, 2)
        assert.equal(page.requests[1].key, 'same-sale-key')
        assert.equal(page.form.items[0].quantity, 1)
    })
}

test('successful completion keeps Save locked against another click', () => {
    const page = createSale()
    page.save()
    page.finish(true)
    page.save()

    assert.equal(page.requests.length, 1)
})
