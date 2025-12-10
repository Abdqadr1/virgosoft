<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">Email</label>
                    <input type="email" v-model="email" class="input" placeholder="Enter your email" required />
                </div>

                <div>
                    <label class="block mb-1 font-medium">Password</label>
                    <input type="password" v-model="password" class="input" placeholder="Enter your password"
                        required />
                </div>

                <button type="submit" class="btn-primary w-full">
                    Login
                </button>
            </form>

            <p v-if="errorMessage" class="text-red-500 mt-2 text-sm">{{ errorMessage }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import api from "../api/axios";
import { useRouter } from "vue-router";

const email = ref("");
const password = ref("");
const errorMessage = ref("");

const router = useRouter();

const submit = async () => {
    errorMessage.value = "";
    try {
        await api.post("/login", {
            email: email.value,
            password: password.value,
        });

        // Fetch user or redirect after successful login
        router.push("/");
    } catch (error) {
        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = "Login failed. Please try again.";
        }
    }
};
</script>

<style scoped>
.input {
    @apply border rounded p-2 w-full;
}

.btn-primary {
    @apply bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition;
}
</style>
