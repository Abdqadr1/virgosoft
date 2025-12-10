export interface Asset {
  symbol: string;
  amount: string; // use string for precision decimal
  locked_amount: string;
}

export interface Order {
  id: number;
  user_id: number;
  symbol: string;
  side: "buy" | "sell";
  price: string;
  amount: string;
  status: 1 | 2 | 3;
  filled_amount: string;
}

export interface Trade {
  id: number;
  buy_order_id: number;
  sell_order_id: number;
  symbol: string;
  price: string;
  amount: string;
  usd_volume: string;
  commission_usd: string;
}

export interface Profile {
  id: number;
  balance: string;
  assets: Asset[];
  orders: Order[];
}
