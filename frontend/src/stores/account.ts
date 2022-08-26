import { useAsyncState, type UseAsyncStateReturn } from '@vueuse/core';
import { defineStore } from 'pinia';
import type { Router } from 'vue-router';
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

interface State {
  account: UseAsyncStateReturn<Account | null, true> | null;
}

export const useAccountStore = defineStore({
  id: 'account',
  state: (): State => ({
    account: useAsyncState<Account | null>(account.get(), null),
  }),
  actions: {
    async logout() {
      this.account = null;
      (this as unknown as { router: Router }).router.push('/login');
      await fetch(`${params.api.host}/logout`, defaultOptions);
    },
  },
});
