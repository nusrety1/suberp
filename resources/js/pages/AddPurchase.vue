<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Customer {
    id: number;
    full_name: string;
}

interface Product {
    id: number;
    name: string;
    price: number;
}

interface ProductItem {
    product_id: number;
    quantity: number;
    purchase_time_unit_price: number;
}

interface PaymentMethod {
    value: string;
    label: string;
    description?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Satışlar',
        href: '/purchases',
    },
    {
        title: 'Satış Ekle',
        href: '/purchase/create',
    },
];

const customers = ref<Customer[]>([]);
const products = ref<Product[]>([]);
const paymentMethods = ref<PaymentMethod[]>([]);
const loading = ref(false);

const form = useForm({
    customer_id: '',
    repayment_data: '',
    bargain_price: '',
    payment_method: '',
    products: [] as ProductItem[]
});

// Toplam tutarı hesapla
const totalAmount = computed(() => {
    return form.products.reduce((total, item) => {
        return total + (item.quantity * item.purchase_time_unit_price);
    }, 0);
});

// Genel toplam (pazarlık fiyatı düşülmüş)
const finalTotal = computed(() => {
    const bargainPrice = parseFloat(form.bargain_price) || 0;
    return Math.max(0, totalAmount.value - bargainPrice);
});

const loadData = async () => {
    loading.value = true;
    try {
        const customersResponse = await fetch('/customers/basic-list');
        const customersData = await customersResponse.json();
        customers.value = customersData.data;

        const productsResponse = await fetch('/products/basic-list');
        const productData = await productsResponse.json()
        products.value = productData.data;

        paymentMethods.value = [
            { value: 'cash', label: 'Nakit', description: 'Peşin nakit ödeme' },
            { value: 'kredi_kartı', label: 'Kredi Kartı', description: 'Kredi kartı ile ödeme' },
            { value: 'imece_kart', label: 'İmece Kart', description: 'İmece Kartı ile ödeme' },
            { value: 'basak_kart', label: 'Başak Kart', description: 'Başak Kart ile ödeme' },
            { value: 'cek', label: 'Çek', description: 'Çek ile ödeme' },
        ];
    } finally {
        loading.value = false;
    }
};

// Yeni ürün satırı ekle
const addProduct = () => {
    form.products.push({
        product_id: 0,
        quantity: 1,
        purchase_time_unit_price: 0
    });
};

// Ürün satırını kaldır
const removeProduct = (index: number) => {
    form.products.splice(index, 1);
};

// Ürün seçildiğinde fiyatını otomatik doldur
const onProductSelect = (index: number, productId: number) => {
    const product = products.value.find(p => p.id == productId);
    if (product) {
        form.products[index].purchase_time_unit_price = product.price;
    }
};

// Form gönder
const submitForm = () => {
    form.post(route('create-purchase'), {
        onSuccess: () => {
            console.log('Satış başarıyla eklendi');
        },
        onError: (errors) => {
            console.log('Form hataları:', errors);
        }
    });
};

// Sayfa yüklendiğinde verileri getir
onMounted(() => {
    loadData();
    // İlk ürün satırını ekle
    addProduct();
});
</script>

<template>
    <Head title="Satış Ekle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Sayfa Başlığı -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Yeni Satış Ekle
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Satış bilgilerini doldurun ve sisteme kaydedin.
                </p>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600 dark:text-gray-400">Veriler yükleniyor...</span>
            </div>

            <!-- Ana Form -->
            <div v-else class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
                <form @submit.prevent="submitForm" class="p-6 sm:p-8">

                    <!-- Müşteri ve Ödeme Bilgileri -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Müşteri Seçimi -->
                        <div class="space-y-2">
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Müşteri <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.customer_id }"
                            >
                                <option value="">Müşteri Seçin</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.id }} - {{ customer.full_name }}
                                </option>
                            </select>
                            <p v-if="form.errors.customer_id" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.customer_id }}
                            </p>
                        </div>

                        <!-- Ödeme Tarihi -->
                        <div class="space-y-2">
                            <label for="repayment_data" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Ödeme Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="repayment_data"
                                v-model="form.repayment_data"
                                type="date"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.repayment_data }"
                            />
                            <p v-if="form.errors.repayment_data" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.repayment_data }}
                            </p>
                        </div>

                        <!-- Ödeme Yöntemi -->
                        <div class="space-y-2">
                            <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Ödeme Yöntemi <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="payment_method"
                                v-model="form.payment_method"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.payment_method }"
                            >
                                <option value="">Ödeme Yöntemi Seçin</option>
                                <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                    {{ method.label }}
                                    <span v-if="method.description" class="text-gray-500"> - {{ method.description }}</span>
                                </option>
                            </select>
                            <p v-if="form.errors.payment_method" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.payment_method }}
                            </p>
                        </div>

                        <!-- Pazarlık Fiyatı -->
                        <div class="space-y-2">
                            <label for="bargain_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Pazarlık İndirimi (₺)
                            </label>
                            <input
                                id="bargain_price"
                                v-model="form.bargain_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="0.00"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.bargain_price }"
                            />
                            <p v-if="form.errors.bargain_price" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.bargain_price }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Toplam tutardan düşülecek indirim miktarı
                            </p>
                        </div>
                    </div>

                    <!-- Seçilen Ödeme Yöntemi Bilgi Kutusu -->
                    <div v-if="form.payment_method" class="mb-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zm8 0a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1h-6a1 1 0 01-1-1v-6z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                    Seçilen Ödeme Yöntemi
                                </h3>
                                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                    <strong>{{ paymentMethods.find(m => m.value === form.payment_method)?.label }}</strong>
                                    <span v-if="paymentMethods.find(m => m.value === form.payment_method)?.description">
                                        - {{ paymentMethods.find(m => m.value === form.payment_method)?.description }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Ürünler Bölümü -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                                Ürünler
                            </h2>
                            <button
                                type="button"
                                @click="addProduct"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                            >
                                + Ürün Ekle
                            </button>
                        </div>

                        <!-- Ürün Listesi -->
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in form.products"
                                :key="index"
                                class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                    <!-- Ürün Seçimi -->
                                    <div class="space-y-2">
                                        <label :for="`product_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Ürün <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            :id="`product_${index}`"
                                            v-model="item.product_id"
                                            @change="onProductSelect(index, item.product_id)"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white transition-colors duration-200"
                                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors[`products.${index}.product_id`] }"
                                        >
                                            <option value="">Ürün Seçin</option>
                                            <option v-for="product in products" :key="product.id" :value="product.id">
                                                {{ product.name }} - {{ product.price }}₺
                                            </option>
                                        </select>
                                        <p v-if="form.errors[`products.${index}.product_id`]" class="text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors[`products.${index}.product_id`] }}
                                        </p>
                                    </div>

                                    <!-- Miktar -->
                                    <div class="space-y-2">
                                        <label :for="`quantity_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Miktar <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            :id="`quantity_${index}`"
                                            v-model="item.quantity"
                                            type="number"
                                            min="1"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white transition-colors duration-200"
                                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors[`products.${index}.quantity`] }"
                                        />
                                        <p v-if="form.errors[`products.${index}.quantity`]" class="text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors[`products.${index}.quantity`] }}
                                        </p>
                                    </div>

                                    <!-- Birim Fiyat -->
                                    <div class="space-y-2">
                                        <label :for="`price_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Birim Fiyat <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            :id="`price_${index}`"
                                            v-model="item.purchase_time_unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white transition-colors duration-200"
                                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors[`products.${index}.purchase_time_unit_price`] }"
                                        />
                                        <p v-if="form.errors[`products.${index}.purchase_time_unit_price`]" class="text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors[`products.${index}.purchase_time_unit_price`] }}
                                        </p>
                                    </div>

                                    <!-- Toplam ve Sil Butonu -->
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Toplam: <span class="font-semibold">{{ (item.quantity * item.purchase_time_unit_price).toFixed(2) }}₺</span>
                                        </div>
                                        <button
                                            v-if="form.products.length > 1"
                                            type="button"
                                            @click="removeProduct(index)"
                                            class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900 rounded-lg transition-colors duration-200"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ara Toplam -->
                        <div class="mt-6 bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900 dark:text-gray-200">
                                    Ara Toplam:
                                </span>
                                <span class="text-xl font-bold text-gray-900 dark:text-gray-200">
                                    {{ totalAmount.toFixed(2) }}₺
                                </span>
                            </div>
                        </div>

                        <!-- Pazarlık İndirimi -->
                        <div v-if="form.bargain_price > 0" class="mt-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-orange-900 dark:text-orange-200">
                                    Pazarlık İndirimi:
                                </span>
                                <span class="text-xl font-bold text-orange-900 dark:text-orange-200">
                                    -{{ parseFloat(form.bargain_price).toFixed(2) }}₺
                                </span>
                            </div>
                        </div>

                        <!-- Genel Toplam -->
                        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-800">
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-blue-900 dark:text-blue-200">
                                    Ödenecek Tutar:
                                </span>
                                <span class="text-2xl font-bold text-blue-900 dark:text-blue-200">
                                    {{ finalTotal.toFixed(2) }}₺
                                </span>
                            </div>
                            <div v-if="form.payment_method" class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                {{ paymentMethods.find(m => m.value === form.payment_method)?.label }} ile ödenecek
                            </div>
                        </div>
                    </div>

                    <!-- Form Butonları -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-4 sm:justify-end">
                        <button
                            type="button"
                            @click="$inertia.visit('/purchases')"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
                        >
                            İptal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 flex items-center justify-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Kaydediliyor...' : 'Satış Ekle' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bilgi Notu -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Bilgi
                        </h3>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            • Zorunlu alanlar (*) işaretli olanlardır.<br>
                            • Ürün seçildiğinde birim fiyat otomatik olarak doldurulur.<br>
                            • Pazarlık indirimi toplam tutardan düşülür.<br>
                            • Ödeme yöntemi seçimi zorunludur.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
