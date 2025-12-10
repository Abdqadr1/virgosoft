import { ref } from "vue";
import { useRouter } from "vue-router";
import api from "../api/axios";
import Swal from "sweetalert2";

const user = ref(null);
const token = ref(null);
const isLoaded = ref(false);

export function useAuth() {
    const router = useRouter();

    // Fetch authenticated user
    const fetchUser = async () => {
        try {
            const { data } = await api.get("/profile");
            user.value = data;
        } catch {
            user.value = null;
        } finally {
            isLoaded.value = true;
        }
    };

    const checkAuth = async () => {
        const token = localStorage.getItem("auth_token");
        if (!token) {
            router.push("/login");
            return;
        }
        user.value = token;
    };

    const login = async (email, password) => {
        const response = await api.post("/authenticate", { email, password });
        if (!response.data) {
            Swal.fire("Login Failed", "Invalid credentials", "error");
            return;
        }
        localStorage.setItem("auth_token", response.data.token);
        localStorage.setItem("auth_user", response.data.user);
        token.value = response.data.token;
        user.value = response.data.user;

        router.push("/");
    };

    const logout = async () => {
        clearStorage();
        router.push("/login");
    };

    const clearStorage = () => {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("auth_user");
    }

    return {
        user,
        isLoaded,
        fetchUser,
        checkAuth,
        login,
        logout,
    };
}
