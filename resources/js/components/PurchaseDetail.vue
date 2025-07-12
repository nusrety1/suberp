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
    purchase_time_unit_price: number
}

interface Purchase {
    id: number
    customer: Customer
    repayment_data: string
    bargain_price: number
    description: string
    created_at: string
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
        const totalReceivableAmount = ref<number>(0)

        // Computed properties
        const totalAmount = computed<number>(() => {
            return products.value.reduce((total: number, product: Product) => {
                return total + (product.quantity * product.purchase_time_unit_price)
            }, 0)
        })

        // Methods
        const closeModal = (): void => {
            emit('close')
        }

        const fetchPurchaseDetails = async (): Promise<void> => {
            if (!props.purchaseId) {
                console.log('purchaseId yok:', props.purchaseId)
                return
            }

            console.log('API isteği atılıyor:', props.purchaseId)
            loading.value = true
            error.value = null

            try {
                const response = await axios.get<ApiResponse>(`/purchases/${props.purchaseId}/details`)
                console.log('API yanıtı:', response.data)

                // API'den gelen veri yapısına göre ayarlama
                if (response.data.purchase && response.data.purchase.length > 0) {
                    purchaseData.value = response.data.purchase[0]
                } else {
                    purchaseData.value = response.data.purchase
                }

                products.value = response.data.products || []
                totalReceivableAmount.value = response.data.total_receivable_amount || 0

                console.log('purchaseData:', purchaseData.value)
                console.log('products:', products.value)
                console.log('totalReceivableAmount:', totalReceivableAmount.value)

            } catch (err: any) {
                error.value = 'Satış detayları yüklenirken bir hata oluştu.'
                console.error('API Error:', err)
            } finally {
                loading.value = false
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
            console.log('isOpen değişti:', newVal, 'purchaseId:', props.purchaseId)
            if (newVal && props.purchaseId) {
                fetchPurchaseDetails()
            }
        }, { immediate: true })

        watch(() => props.purchaseId, (newVal: number | string | null) => {
            console.log('purchaseId değişti:', newVal, 'isOpen:', props.isOpen)
            if (newVal && props.isOpen) {
                fetchPurchaseDetails()
            }
        }, { immediate: true })

        return {
            loading,
            error,
            purchaseData,
            products,
            totalAmount,
            totalReceivableAmount,
            closeModal,
            formatDate,
            formatCurrency
        }
    }
}
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>

        <!-- Modal -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl transform transition-all">
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
                <div class="p-6 max-h-96 overflow-y-auto">
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
                                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(purchaseData.repayment_data) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Pazarlık Fiyatı</label>
                                    <p class="mt-1 text-sm text-gray-900 font-semibold text-green-600">
                                        {{ formatCurrency(purchaseData.bargain_price) }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ purchaseData.description || 'Açıklama yok' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Oluşturulma Tarihi</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(purchaseData.created_at) }}</p>
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
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">Miktar</th>
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">
                                            Birim Fiyat (Satış Yapıldığı Tarihte)
                                        </th>
                                        <th class="text-left py-2 px-3 text-sm font-medium text-gray-700">Toplam</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="product in products" :key="product.id" class="border-b border-gray-100">
                                        <td class="py-2 px-3 text-sm text-gray-900">{{ product.product_id }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-900">{{ product.quantity }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-900">{{ formatCurrency(product.purchase_time_unit_price) }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-900 font-semibold">
                                            {{ formatCurrency(product.quantity * product.purchase_time_unit_price) }}
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Toplam Tutarlar -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Satış Yapıldığı Zaman Toplam Tutar:</span>
                                <span class="text-xl font-bold text-blue-600">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Güncel Tahsil Edilecek Tutar:</span>
                                <span class="text-xl font-bold text-green-600">
                                    {{ formatCurrency(totalReceivableAmount) }}
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
