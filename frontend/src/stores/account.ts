import { useAsyncState } from '@vueuse/core';
import { defineStore } from 'pinia';
import params from '../../config/parameters';
import { defaultOptions } from '../utils/api';

const account = {
  get: async () => {
    const res = await fetch(`${params.api.host}/user`, defaultOptions);
    return res.json();
  },
};

export interface Account {
  id: string;
  count: number;
  slack_name: string;
  slack_picture: string;
}

export const useAccountStore = defineStore({
  id: 'account',
  state: () => ({
    account: useAsyncState<Account | null>(account.get(), null),
  }),
});
