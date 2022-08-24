import { useAsyncState } from '@vueuse/core'
import { defineStore } from 'pinia'
import { mande } from 'mande'
import params from '../../config/parameters'

const interactions = mande(`${params.api.host}/messages`)

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
    interactions: useAsyncState<Interaction[]>(
      interactions.get(''),
      null,
    ),
  }),
})
