<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SupplyPaymentModal from '@/components/SupplyPaymentModal.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import type { BreadcrumbItem } from '@/types';

interface Customer {
    id: number;
    name: string;
    surname: string;
    full_name: string;
}

interface Supply {
    id: number;
    name: string;
    slug: string;
    amount: number;
    paid_amount: number;
    quantity: number;
    unit: string;
    description: string;
    customer_id: number | null;
    customer: Customer | null;
    created_at: string;
    updated_at: string;
}

interface PaginatedSupplies {
    data: Supply[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url?: string;
    prev_page_url?: string;
}

interface Props {
    supplies: PaginatedSupplies;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Malzemeler',
        href: '/supplies',
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatAmount = (amount: number) => {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
};

const formatQuantity = (quantity: number, unit: string) => {
    return `${quantity} ${unit}`;
};

// Payment modal state
const isPaymentModalOpen = ref(false);
const selectedSupply = ref<Supply | null>(null);
const customerTotalDebt = ref<number>(0);

// Payment modal functions
const openPaymentModal = async (supply: Supply) => {
    selectedSupply.value = supply;
    isPaymentModalOpen.value = true;

    // Fetch customer total debt if supply has a customer
    if (supply.customer_id) {
        try {
            const response = await axios.get(`/customer/${supply.customer_id}/supply-debt`);
            customerTotalDebt.value = response.data.total_debt;
        } catch (error) {
            console.error('Error fetching customer debt:', error);
            customerTotalDebt.value = 0;
        }
    } else {
        customerTotalDebt.value = 0;
    }
};

const closePaymentModal = () => {
    isPaymentModalOpen.value = false;
    selectedSupply.value = null;
    customerTotalDebt.value = 0;
};
</script>

<template>
    <Head title="Malzemeler" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Teminler</h1>
                    <p class="text-gray-600 mt-1">Toplam {{ props.supplies.total }} malzeme</p>
                </div>
                <button
                    @click="$inertia.visit('/add-supply')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Yeni Malzeme Temin Et
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ürün
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Müşteri
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Miktar
                            </th>
<!--                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">-->
<!--                                Toplam Malzeme Değeri-->
<!--                            </th>-->
<!--                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">-->
<!--                                Toplam Ödenen Tutar-->
<!--                            </th>-->
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Açıklama
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kayıt Tarihi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Toplam Malzeme Değeri
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Toplam Ödenen Tutar
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kalan Borç
                            </th>
                            <th>
                                <!-- // -->
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="supply in props.supplies.data" :key="supply.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-green-600">
                                                    {{ supply.name.charAt(0).toUpperCase() }}
                                                </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ supply.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            ID: #{{ supply.id }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="supply.customer" class="text-sm text-gray-900">
                                    {{ supply.customer.full_name }}
                                </div>
                                <div v-else class="text-sm text-gray-500 italic">
                                    Müşteri atanmamış
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ formatQuantity(supply.quantity, supply.unit) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ supply.description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(supply.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatAmount(supply.amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatAmount(supply.paid_amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="(supply.amount - supply.paid_amount) > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatAmount(supply.amount - supply.paid_amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button
                                        @click="openPaymentModal(supply)"
                                        :disabled="(supply.amount - supply.paid_amount) <= 0"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 font-medium rounded-md transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        Ödeme
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6" v-if="props.supplies.last_page > 1">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button
                            v-if="props.supplies.prev_page_url"
                            @click="$inertia.visit(props.supplies.prev_page_url)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Önceki
                        </button>
                        <button
                            v-if="props.supplies.next_page_url"
                            @click="$inertia.visit(props.supplies.next_page_url)"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Sonraki
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Toplam <span class="font-medium">{{ props.supplies.total }}</span> sonuçtan
                                <span class="font-medium">{{ ((props.supplies.current_page - 1) * props.supplies.per_page) + 1 }}</span>
                                -
                                <span class="font-medium">{{ Math.min(props.supplies.current_page * props.supplies.per_page, props.supplies.total) }}</span>
                                arası gösteriliyor
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button
                                    v-if="props.supplies.prev_page_url"
                                    @click="$inertia.visit(props.supplies.prev_page_url)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                    {{ props.supplies.current_page }} / {{ props.supplies.last_page }}
                                </span>

                                <button
                                    v-if="props.supplies.next_page_url"
                                    @click="$inertia.visit(props.supplies.next_page_url)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="props.supplies.data.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz temin edilmiş malzeme yok</h3>
                <p class="mt-1 text-sm text-gray-500">İlk malzemenizi temin edin.</p>
                <div class="mt-6">
                    <button
                        @click="$inertia.visit('/add-supply')"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Yeni Malzeme Temin Et
                    </button>
                </div>
            </div>
        </div>

        <!-- Supply Payment Modal -->
        <SupplyPaymentModal
            :supply="selectedSupply"
            :is-open="isPaymentModalOpen"
            :customer-total-debt="customerTotalDebt"
            @close="closePaymentModal"
        />
    </AppLayout>
</template>
