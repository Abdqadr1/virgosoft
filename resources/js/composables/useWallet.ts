import { ref } from "vue";
import api from "../api/axios";
import { Profile } from "..";

const profile = ref<Profile | null>(null);

export function useWallet() {
    const fetchProfile = async (): Promise<void> => {
        const { data } = await api.get<Profile>('/profile');
        profile.value = data;
    };

    return {
        profile,
        fetchProfile
    };
}
