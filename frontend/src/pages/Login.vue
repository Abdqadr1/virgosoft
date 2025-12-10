<template>
    <div>
        <NavBar />
        <div class="min-h-screen flex items-center justify-center bg-gray-100 pt-8">
            <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
                <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>

                <form @submit.prevent="submit" class="space-y-4 flex flex-col">
                    <div class="flex flex-col">
                        <label class="block mb-1 font-medium">Email</label>
                        <input type="email" v-model="email" class="input" placeholder="Enter your email" required />
                    </div>

                    <div class="flex flex-col">
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
    </div>
</template>

<script setup>
import { ref } from "vue";
import api from "../api/axios";
import { useRouter } from "vue-router";
import NavBar from "../components/NavBar.vue";
import useAuth from "../composables/useAuth";

const email = ref("");
const password = ref("");
const errorMessage = ref("");
const router = useRouter();
const { setToken } = useAuth();

const submit = async () => {
    errorMessage.value = "";
    try {
        const res = await api.post("/login", {
            email: email.value,
            password: password.value,
        });

        // assume the API returns a token at res.data.token
        const token = res.data?.token;
        if (token) {
            setToken(token);
            router.push("/dashboard");
        } else {
            // fallback: if no token returned, just redirect
            router.push("/");
        }
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
/* replaced @apply usage because Tailwind's @apply can fail in scoped/CSS-module contexts.
   Use equivalent plain CSS so the Tailwind plugin no longer sees unknown utilities. */

.input {
    border: 1px solid #e5e7eb;
    /* tailwind border-gray-200 */
    border-radius: 0.375rem;
    /* tailwind rounded */
    padding: 0.5rem 0.75rem;
    /* tailwind px-3 py-2 */
    outline: none;
}

.input:focus {
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.08);
    /* subtle indigo ring */
    border-color: #c7d2fe;
    /* tailwind ring-indigo-200-ish */
}

.btn-primary {
    background-color: #4f46e5;
    /* tailwind indigo-600 */
    color: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: none;
}

.btn-primary:hover {
    background-color: #4338ca;
    /* tailwind indigo-700 */
}
</style>
