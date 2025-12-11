import { ref, watch } from "vue";
import { useRouter } from "vue-router";
import api from "../api/axios";
import Swal from "sweetalert2";

const token = ref(localStorage.getItem("auth_token") || "");
const isLoggedIn = ref(!!token.value);

watch(token, (val) => {
    if (val) localStorage.setItem("auth_token", val);
    else localStorage.removeItem("auth_token");
    isLoggedIn.value = !!val;
});

export default function useAuth() {
    const router = useRouter();

    // Fetch authenticated user
    const fetchUser = async () => {
        const { data } = await api.get("/profile");
        return data;
    };

    const checkAuth = async () => {
        const t = localStorage.getItem("auth_token");
        if (!t) {
            router.push("/login");
            return false;
        }
        token.value = t;
        return true;
    };

    const login = async (email, password, route = '/') => {
        try {
            const response = await api.post("/authenticate", { email, password });
            if (!response.data || !response.data.token) {
                Swal.fire("Login Failed", "Invalid credentials", "error");
                return;
            }
            // persist token and user
            token.value = response.data.token;
            user.value = response.data.user || null;
            localStorage.setItem("auth_user", JSON.stringify(response.data.user || null));
            localStorage.setItem("auth_token", JSON.stringify(response.data.token || null));

            // redirect after login
            router.push(route);
        } catch (err) {
            Swal.fire("Login Failed", err?.response?.data?.message || "Invalid credentials", "error");
        }
    };

    const logout = async () => {
        token.value = "";
        user.value = null;
        clearStorage();
        router.push("/login");
    };

    const clearStorage = () => {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("auth_user");
    };

    const setToken = (t) => {
        token.value = t;
        localStorage.setItem("auth_token", t);
    };

    const clear = () => {
        clearStorage();
        token.value = "";
        user.value = null;
    };

    return {
        fetchUser,
        checkAuth,
        login,
        logout,
        token,
        isLoggedIn,
        setToken,
        clear,
    };
}
