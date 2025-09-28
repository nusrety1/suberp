<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface Supply {
    id: number;
    name: string;
    amount: number;
    paid_amount: number;
    quantity: number;
    unit: string;
    description: string;
    created_at: string;
}

interface Props {
    supply: Supply | null;
    isOpen: boolean;
}

interface Emits {
    (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Form state
const paymentAmount = ref<number>(0);
const paymentDescription = ref<string>('');
const isSubmitting = ref<boolean>(false);
const errors = ref<Record<string, string>>({});

// Computed values
const remainingDebt = computed(() => {
    if (!props.supply) return 0;
    return props.supply.amount - props.supply.paid_amount;
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
};

const formatQuantity = (quantity: number, unit: string) => {
    return `${quantity} ${unit}`;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Reset form when modal opens/closes
watch(() => props.isOpen, (newValue) => {
    if (newValue) {
        resetForm();
    }
});

const resetForm = () => {
    paymentAmount.value = remainingDebt.value > 0 ? remainingDebt.value : 0;
    paymentDescription.value = '';
    errors.value = {};
};

const validateForm = (): boolean => {
    errors.value = {};

    if (paymentAmount.value <= 0) {
        errors.value.paymentAmount = 'Ödeme tutarı 0\'dan büyük olmalıdır.';
    }

    if (paymentAmount.value > remainingDebt.value) {
        errors.value.paymentAmount = 'Ödeme tutarı kalan borçtan fazla olamaz.';
    }

    return Object.keys(errors.value).length === 0;
};

const submitPayment = async () => {
    if (!props.supply || !validateForm()) return;

    isSubmitting.value = true;

    try {
        await router.post('/supply_paid', {
            id: props.supply.id,
            paid_amount: paymentAmount.value,
            description: paymentDescription.value
        }, {
            preserveState: false,
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                if (typeof errors === 'object') {
                    Object.keys(errors).forEach(key => {
                        if (key === 'paid_amount') {
                            errors.value.paymentAmount = errors[key];
                        } else {
                            errors.value[key] = errors[key];
                        }
                    });
                }
            }
        });
    } catch (error) {
        console.error('Payment submission error:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const closeModal = () => {
    if (!isSubmitting.value) {
        emit('close');
    }
};

// Handle ESC key
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && props.isOpen && !isSubmitting.value) {
        closeModal();
    }
};

// Add event listener when component mounts
watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        document.addEventListener('keydown', handleKeydown);
    } else {
        document.removeEventListener('keydown', handleKeydown);
    }
});
</script>

<template>
    <!-- Modal Backdrop -->
    <div
        v-if="isOpen && supply"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
    >
        <!-- Modal Content -->
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 h-12 w-12">
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                            <span class="text-lg font-medium text-green-600">
                                {{ supply.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Malzeme Borç Ödemesi</h3>
                        <p class="text-sm text-gray-500">{{ supply.name }}</p>
                    </div>
                </div>
                <button
                    @click="closeModal"
                    :disabled="isSubmitting"
                    class="text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition-colors duration-200"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4">
                <!-- Malzeme Bilgileri -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Malzeme Detayları</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Malzeme ID</p>
                            <p class="text-sm font-medium text-gray-900">#{{ supply.id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Miktar</p>
                            <p class="text-sm font-medium text-gray-900">{{ formatQuantity(supply.quantity, supply.unit) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Toplam Değer</p>
                            <p class="text-sm font-medium text-gray-900">{{ formatCurrency(supply.amount) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Ödenen Tutar</p>
                            <p class="text-sm font-medium text-gray-900">{{ formatCurrency(supply.paid_amount) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Kalan Borç</p>
                            <p class="text-sm font-medium" :class="remainingDebt > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(remainingDebt) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Alış Tarihi</p>
                            <p class="text-sm font-medium text-gray-900">{{ formatDate(supply.created_at) }}</p>
                        </div>
                    </div>
                    <div v-if="supply.description" class="mt-4">
                        <p class="text-sm text-gray-500">Açıklama</p>
                        <p class="text-sm font-medium text-gray-900">{{ supply.description }}</p>
                    </div>
                </div>

                <!-- Ödeme Formu -->
                <form @submit.prevent="submitPayment" class="space-y-4">
                    <div>
                        <label for="paymentAmount" class="block text-sm font-medium text-gray-700 mb-1">
                            Ödeme Tutarı <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="paymentAmount"
                                v-model.number="paymentAmount"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="remainingDebt"
                                :disabled="isSubmitting"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': errors.paymentAmount }"
                                placeholder="0.00"
                            />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <span class="text-gray-500 text-sm">₺</span>
                            </div>
                        </div>
                        <p v-if="errors.paymentAmount" class="mt-1 text-sm text-red-600">
                            {{ errors.paymentAmount }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Maksimum ödenebilir tutar: {{ formatCurrency(remainingDebt) }}
                        </p>
                    </div>

                    <div>
                        <label for="paymentDescription" class="block text-sm font-medium text-gray-700 mb-1">
                            Açıklama
                        </label>
                        <textarea
                            id="paymentDescription"
                            v-model="paymentDescription"
                            rows="3"
                            :disabled="isSubmitting"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                            placeholder="Ödeme ile ilgili notlar..."
                        ></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            @click="closeModal"
                            :disabled="isSubmitting"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                        >
                            İptal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting || remainingDebt <= 0"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                        >
                            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isSubmitting ? 'Ödeme Yapılıyor...' : 'Ödeme Yap' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
