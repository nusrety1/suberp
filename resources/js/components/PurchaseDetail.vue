<script lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'

interface Customer {
    id: number
    full_name: string
    email: string
    phone: string
}

interface Product {
    id: number
    product_id: number
    quantity: number
    product: object
    purchase_time_unit_price: number
}

interface Purchase {
    id: number
    customer: Customer
    repayment_date: Date
    bargain_price: number
    description: string
    created_at: string
    payment_method: string
    paid_amount: number
}

interface ApiResponse {
    purchase: Purchase | Purchase[]
    products: Product[]
    total_receivable_amount: number
}

export default {
    name: 'PurchaseDetailsModal',
    props: {
        isOpen: {
            type: Boolean,
            default: false
        },
        purchaseId: {
            type: [Number, String],
            default: null
        }
    },
    emits: ['close'],
    setup(props: any, { emit }: any) {
        const loading = ref<boolean>(false)
        const error = ref<string | null>(null)
        const purchaseData = ref<Purchase | null>(null)
        const products = ref<Product[]>([])
        const payments = ref<any[]>([])
        const totalReceivableAmount = ref<number>(0)

        // Payment form state
        const paymentForm = ref({
            newPayment: 0,
            selectedProductId: null as number | null,
            description: '',
            productQuantity: 1,
            loading: false,
            success: false,
            error: null as string | null
        })

        // Computed properties
        const totalAmount = computed<number>(() => {
            return products.value.reduce((total: number, product: Product) => {
                return total + (product.quantity * product.purchase_time_unit_price)
            }, 0)
        })

        const currentPaidAmount = computed<number>(() => {
            return purchaseData.value?.paid_amount || 0
        })

        const newTotalPaidAmount = computed<number>(() => {
            return currentPaidAmount.value + (paymentForm.value.newPayment || 0)
        })

        const remainingAmount = computed<number>(() => {
            return totalReceivableAmount.value - currentPaidAmount.value
        })

        const newRemainingAmount = computed<number>(() => {
            return totalReceivableAmount.value - newTotalPaidAmount.value
        })

        // Selected product remaining quantity
        const selectedProductRemainingQuantity = computed<number>(() => {
            if (!paymentForm.value.selectedProductId) return 0
            
            const product = products.value.find(p => p.product_id === paymentForm.value.selectedProductId)
            if (!product) return 0

            // Calculate total paid quantity for this product
            const totalPaidQuantity = payments.value
                .filter(p => p.product_id === paymentForm.value.selectedProductId)
                .reduce((sum, p) => sum + (p.product_quantity || 0), 0)

            return product.quantity - totalPaidQuantity
        })

        // Methods
        const closeModal = (): void => {
            // Reset payment form when closing
            paymentForm.value = {
                newPayment: 0,
                selectedProductId: null,
                description: '',
                productQuantity: 1,
                loading: false,
                success: false,
                error: null
            }
            emit('close')
        }

        const fetchPurchaseDetails = async (): Promise<void> => {
            if (!props.purchaseId) {
                return
            }

            loading.value = true
            error.value = null

            try {
                const response = await axios.get<ApiResponse>(`/purchases/${props.purchaseId}/details`)
                console.log(response.data);
                if (response.data.purchase && Array.isArray(response.data.purchase) && response.data.purchase.length > 0) {
                    purchaseData.value = response.data.purchase[0]
                } else {
                    purchaseData.value = response.data.purchase as Purchase
                }

                products.value = response.data.products || []
                payments.value = response.data.payments || []
                totalReceivableAmount.value = response.data.total_receivable_amount || 0

            } catch (err: any) {
                error.value = 'Satış detayları yüklenirken bir hata oluştu.'
                console.error('API Error:', err)
            } finally {
                loading.value = false
            }
        }

        const submitPartialPayment = async (): Promise<void> => {
            if (!purchaseData.value || !paymentForm.value.newPayment || paymentForm.value.newPayment <= 0) {
                paymentForm.value.error = 'Geçerli bir ödeme tutarı giriniz.'
                return
            }

            if (!paymentForm.value.selectedProductId) {
                paymentForm.value.error = 'Lütfen bir ürün seçiniz.'
                return
            }

            if (!paymentForm.value.productQuantity || paymentForm.value.productQuantity <= 0) {
                paymentForm.value.error = 'Geçerli bir ürün adedi giriniz.'
                return
            }

            paymentForm.value.loading = true
            paymentForm.value.error = null
            paymentForm.value.success = false

            try {
                const response = await axios.post('/purchase/partial-payment', {
                    purchase_id: purchaseData.value.id,
                    product_id: paymentForm.value.selectedProductId,
                    paid_amount: newTotalPaidAmount.value,
                    payment_amount: paymentForm.value.newPayment,
                    description: paymentForm.value.description,
                    product_quantity: paymentForm.value.productQuantity
                })

                if (response.data.success) {
                    // Update local data
                    if (purchaseData.value) {
                        purchaseData.value.paid_amount = newTotalPaidAmount.value
                    }

                    paymentForm.value.success = true
                    paymentForm.value.newPayment = 0
                    paymentForm.value.selectedProductId = null
                    paymentForm.value.description = ''
                    paymentForm.value.productQuantity = 1

                    // Refresh payments list
                    await fetchPurchaseDetails()

                    // Hide success message after 3 seconds
                    setTimeout(() => {
                        paymentForm.value.success = false
                    }, 3000)
                }
            } catch (err: any) {
                if (err.response?.data?.error) {
                    paymentForm.value.error = err.response.data.error
                } else {
                    paymentForm.value.error = 'Ödeme kaydedilirken bir hata oluştu.'
                }
                console.error('Payment Error:', err)
            } finally {
                paymentForm.value.loading = false
            }
        }

        const formatDate = (dateString: string | null): string => {
            if (!dateString) return 'N/A'

            const date = new Date(dateString)
            return date.toLocaleDateString('tr-TR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }

        const formatCurrency = (amount: number | null): string => {
            if (!amount && amount !== 0) return 'N/A'

            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY'
            }).format(amount)
        }

        // Watchers
        watch(() => props.isOpen, (newVal: boolean) => {
            if (newVal && props.purchaseId) {
                fetchPurchaseDetails()
            }
        }, { immediate: true })

        watch(() => props.purchaseId, (newVal: number | string | null) => {
            if (newVal && props.isOpen) {
                fetchPurchaseDetails()
            }
        }, { immediate: true })

        // Reset product quantity when product selection changes
        watch(() => paymentForm.value.selectedProductId, () => {
            paymentForm.value.productQuantity = 1
        })

        return {
            loading,
            error,
            purchaseData,
            products,
            payments,
            totalAmount,
            totalReceivableAmount,
            paymentForm,
            currentPaidAmount,
            newTotalPaidAmount,
            remainingAmount,
            newRemainingAmount,
            selectedProductRemainingQuantity,
            closeModal,
            formatDate,
            formatCurrency,
            submitPartialPayment,
        }
    }
}
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-[90%] h-[90vh] bg-white rounded-lg shadow-xl transform transition-all">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Satış Bilgilendirme
                    </h3>
                    <button
                        @click="closeModal"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full p-1"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 h-[calc(90vh-80px)] overflow-y-auto">
                    <!-- Loading State -->
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-2 text-gray-600">Yükleniyor...</span>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="text-center py-8">
                        <div class="text-red-500 mb-2">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600">{{ error }}</p>
                    </div>

                    <!-- Debug Info -->
                    <div v-else-if="!purchaseData" class="text-center py-8">
                        <p class="text-gray-600">Veri yükleniyor veya bulunamadı...</p>
                        <p class="text-sm text-gray-400 mt-2">Purchase ID: {{ purchaseId }}</p>
                        <p class="text-sm text-gray-400">Loading: {{ loading }}</p>
                        <p class="text-sm text-gray-400">Error: {{ error }}</p>
                    </div>

                    <!-- Content -->
                    <div v-else class="space-y-6">
                        <!-- Müşteri Bilgileri -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Müşteri Bilgileri
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">ID</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ purchaseData.customer?.id || 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Ad Soyad</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ purchaseData.customer?.full_name || 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">E-posta</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ purchaseData.customer?.email || 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Telefon</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ purchaseData.customer?.phone || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Satış Bilgileri -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                                Satış Bilgileri
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Geri Ödeme Tarihi</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(purchaseData.repayment_date) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Pazarlık Fiyatı</label>
                                    <p class="mt-1 text-sm text-gray-900 font-semibold text-green-600">
                                        {{ formatCurrency(purchaseData.bargain_price) }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                                    <p class="mt-1 text-sm text-gray-900 bg-white p-2 rounded border border-gray-200 min-h-[60px]">
                                        {{ purchaseData.description || 'Açıklama yok' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Oluşturulma Tarihi</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(purchaseData.created_at) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Ödeme Yöntemi</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ purchaseData.payment_method }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Ürün Bilgileri -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                Ürün Bilgileri
                            </h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">Ürün ID</th>
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">Ürün Adı</th>
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">Miktar</th>
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">
                                            Birim Fiyat (Satış Yapıldığı Tarihte)
                                        </th>
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">Ürün Güncel Fiyatı</th>
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">Toplam</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="product in products" :key="product.id" class="border-b border-gray-100">
                                        <td class="py-2 px-3 text-sm text-gray-900">{{ product.product_id }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-900 font-medium">{{ product.product.name }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-900">{{ product.quantity }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-900">{{ formatCurrency(product.purchase_time_unit_price) }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-900">{{ formatCurrency(product.product.price) }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-900 font-semibold">
                                            {{ formatCurrency(product.quantity * product.purchase_time_unit_price) }}
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Ödeme Bilgileri -->
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Ödeme Bilgileri ve Kısmi Ödeme
                            </h4>

                            <!-- Current Payment Info -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Güncel Tahsil Edilecek Tutar (Ürünlerin Güncel Fiyatlarıyla)</label>
                                    <p class="mt-1 text-sm text-gray-900 font-semibold text-green-600">
                                        {{ formatCurrency(totalReceivableAmount) }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Toplam Ödenen Tutar</label>
                                    <p class="mt-1 text-sm text-gray-900 font-semibold text-blue-600">
                                        {{ formatCurrency(currentPaidAmount) }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kalan Ödenecek Tutar</label>
                                    <p class="mt-1 text-sm text-gray-900 font-semibold text-red-600">
                                        {{ formatCurrency(remainingAmount) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Payment Form -->
                            <form @submit.prevent="submitPartialPayment" class="space-y-4">
                                <!-- Product Selection -->
                                <div>
                                    <label for="selectedProduct" class="block text-sm font-medium text-gray-700">
                                        Ürün Seçimi
                                    </label>
                                    <div class="mt-1">
                                        <select
                                            id="selectedProduct"
                                            v-model.number="paymentForm.selectedProductId"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            :disabled="paymentForm.loading"
                                        >
                                            <option value="">Ürün seçiniz...</option>
                                            <option
                                                v-for="product in products"
                                                :key="product.id"
                                                :value="product.product_id"
                                            >
                                                {{ product.product.name }} ({{ product.quantity }} adet - Kalan: {{ selectedProductRemainingQuantity }} adet)
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Product Quantity -->
                                <div>
                                    <label for="productQuantity" class="block text-sm font-medium text-gray-700">
                                        Ürün Adedi
                                    </label>
                                    <div class="mt-1 relative">
                                        <input
                                            id="productQuantity"
                                            v-model.number="paymentForm.productQuantity"
                                            type="number"
                                            min="1"
                                            :max="selectedProductRemainingQuantity"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Kaç adet için ödeme yapıyorsunuz?"
                                            :disabled="paymentForm.loading"
                                        >
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">
                                        Açıklama (İsteğe Bağlı)
                                    </label>
                                    <div class="mt-1 relative">
                                        <textarea
                                            id="description"
                                            v-model="paymentForm.description"
                                            rows="3"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Ödeme açıklaması giriniz..."
                                            :disabled="paymentForm.loading"
                                        ></textarea>
                                    </div>
                                </div>

                                <div>
                                    <label for="newPayment" class="block text-sm font-medium text-gray-700">
                                        Yeni Ödeme Tutarı
                                    </label>
                                    <div class="mt-1 relative">
                                        <input
                                            id="newPayment"
                                            v-model.number="paymentForm.newPayment"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :max="totalReceivableAmount - currentPaidAmount"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Ödeme tutarını giriniz"
                                            :disabled="paymentForm.loading"
                                        >
                                    </div>
                                </div>

                                <!-- Payment Summary -->
                                <div v-if="paymentForm.newPayment > 0" class="bg-gray-100 rounded-lg p-3">
                                    <div class="text-sm space-y-1">
                                        <div class="flex justify-between">
                                            <span>Güncel tahsil edilecek tutar:</span>
                                            <span class="font-medium text-green-600">{{ formatCurrency(totalReceivableAmount) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Mevcut ödenen:</span>
                                            <span class="font-medium">{{ formatCurrency(currentPaidAmount) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Yeni ödeme:</span>
                                            <span class="font-medium">{{ formatCurrency(paymentForm.newPayment) }}</span>
                                        </div>
                                        <div class="flex justify-between border-t pt-1 font-semibold">
                                            <span>Yeni toplam ödenecek:</span>
                                            <span class="text-blue-600">{{ formatCurrency(newTotalPaidAmount) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Yeni kalan ödenecek tutar:</span>
                                            <span class="text-red-600">{{ formatCurrency(newRemainingAmount) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Error Message -->
                                <div v-if="paymentForm.error" class="text-red-600 text-sm">
                                    {{ paymentForm.error }}
                                </div>

                                <!-- Success Message -->
                                <div v-if="paymentForm.success" class="text-green-600 text-sm flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Ödeme başarıyla kaydedildi!
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    :disabled="paymentForm.loading || paymentForm.newPayment <= 0"
                                    class="w-full flex justify-center items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    <div v-if="paymentForm.loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                    {{ paymentForm.loading ? 'Kaydediliyor...' : 'Ödemeyi Kaydet' }}
                                </button>
                            </form>

                            <!-- Payment Records Table -->
                            <div v-if="payments && payments.length > 0" class="mt-6">
                                <h5 class="text-md font-medium text-gray-900 mb-3">Ödeme Kayıtları</h5>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Satış ID
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ürün Adı
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ürün Adedi
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ödenen Tutar
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Açıklama
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ödeme Tarihi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="payment in payments" :key="payment.id" class="hover:bg-gray-50">
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                    {{ payment.purchase_id }}
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                    {{ payment.product?.name || 'N/A' }}
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                    {{ payment.product_quantity || 'N/A' }} adet
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 font-medium">
                                                    {{ formatCurrency(payment.paid_amount) }}
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                    {{ payment.description || '-' }}
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                    {{ formatDate(payment.created_at) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Toplam Tutarlar -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Satış Yapıldığı Zaman Toplam Tutar (Eski Fiyatlarla):</span>
                                <span class="text-xl font-bold text-blue-600">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>

                        <div class="bg-gray-100 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Pazarlık İndirim Tutarı:</span>
                                <span class="text-xl font-bold text-gray-600">{{ formatCurrency(purchaseData.bargain_price) }}</span>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Tahsil Edilecek Toplam Tutar (Ürünlerin Güncel Fiyatlarıyla):</span>
                                <span class="text-xl font-bold text-green-600">
                                    {{ formatCurrency(totalReceivableAmount) }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Toplam Ödenen Tutar:</span>
                                <span class="text-xl font-bold text-blue-600">
                                    {{ formatCurrency(currentPaidAmount) }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Kalan Ödenecek Tutar:</span>
                                <span class="text-xl font-bold text-red-600">
                                    {{ formatCurrency(remainingAmount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end p-6 border-t border-gray-200">
                    <button
                        @click="closeModal"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors"
                    >
                        Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for webkit browsers */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
