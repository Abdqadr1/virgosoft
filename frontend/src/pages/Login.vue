<template>
    <div>
        <NavBar />
        <div class="min-h-screen flex items-center justify-center bg-gray-100 pt-8">
            <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
                <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>

                <form @submit.prevent="submit" class="space-y-4 flex flex-col">
                    <div class="flex flex-col">
                        <label class="block mb-1 font-medium">Email</label>
                        <input type="email" v-model="email"
                            class="border border-gray-200 rounded px-3 py-2 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-200"
                            placeholder="Enter your email" required />
                    </div>

                    <div class="flex flex-col">
                        <label class="block mb-1 font-medium">Password</label>
                        <input type="password" v-model="password"
                            class="border border-gray-200 rounded px-3 py-2 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-200"
                            placeholder="Enter your password" required />
                    </div>

                    <button type="submit"
                        class="bg-indigo-600 text-white px-4 py-2 rounded inline-flex items-center justify-center cursor-pointer border-0 hover:bg-indigo-700 w-full disabled:opacity-60 disabled:cursor-not-allowed"
                        :disabled="isSubmitting">
                        {{ isSubmitting ? 'Logging in...' : 'Login' }}
                    </button>
                </form>

                <p v-if="errorMessage" class="text-red-500 mt-2 text-sm">{{ errorMessage }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import NavBar from "../components/NavBar.vue";
import useAuth from "../composables/useAuth";

const email = ref("");
const password = ref("");
const errorMessage = ref("");
const isSubmitting = ref(false);
const { login } = useAuth();

const submit = async () => {
    errorMessage.value = "";
    if (!email.value || !password.value) {
        errorMessage.value = "Please enter both email and password.";
        return;
    }
    // disable the button or show loading state in a real app

    isSubmitting.value = true;
    login(email.value, password.value, '/')
        .finally(() => {
            isSubmitting.value = false;
        });
};
</script>
