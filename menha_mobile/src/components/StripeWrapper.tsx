import React from 'react';
import { StripeProvider } from '@stripe/stripe-react-native';

export const StripeWrapper = ({ children }: any) => (
  <StripeProvider
    publishableKey="pk_test_placeholder"
    merchantIdentifier="merchant.com.menhaboutique.mobile"
  >
    {children}
  </StripeProvider>
);
