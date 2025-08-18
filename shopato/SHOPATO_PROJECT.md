# Shopato Implementation Plan

The shopato microservice handles creating gift cards on Shopify.
It is a Rails app.

## GibPotato - Shopato Authentication

We will use an `Authorization` header with a static value.

## Application

We need one API endpoint where GibPotato can make an order for a new gift card. This is a POST JSON request that contains an email address and gift card amount. The amount needs to be validated based on an enum with the values of $25, $50 and $100.
Once a request comes in, create a new gift card using Shopify's GraphQL API.
