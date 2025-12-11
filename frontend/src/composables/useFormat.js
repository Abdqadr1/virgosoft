// composables/useFormat.ts
import { Decimal } from "decimal.js";

export function useFormat() {
    function formatCrypto(value) {
        const d = new Decimal(value);

        // Convert to string without scientific notation
        const str = d.toFixed();

        if (!str.includes(".")) {
            // Whole number → show 2 decimals
            return d.toFixed(2);
        }

        const [intPart, decPart] = str.split(".");

        // Check if decimal part contains only zeros
        const isAllZero = /^0+$/.test(decPart);

        if (isAllZero) {
            return `${intPart}.00`; // fixed 2 decimals
        }

        // Otherwise remove trailing zeros but keep real decimals
        const trimmedDec = decPart.replace(/0+$/, "");

        return `${intPart}.${trimmedDec}`;
    }

    return { formatCrypto };
}
