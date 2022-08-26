import { useAsyncState } from '@vueuse/core'
import { defineStore } from 'pinia'
import params from '../../config/parameters'
import { defaultOptions } from '../utils/api'

const interactions = {
  get: async () => {
    const res = await fetch(`${params.api.host}/messages`, defaultOptions);
    return res.json();
  }
}

export interface Interaction {
  id: string;
  sender_id: string;
  sender_name: string;
  sender_picture: string;
  receiver_id: string;
  receiver_name: string;
  amount: number;
}

export const useInteractionsStore = defineStore({
  id: 'interactions',
  state: () => ({
    interactions: useAsyncState<Interaction[] | null>(
      interactions.get(),
      null,
    ),
  }),
})
