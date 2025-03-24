#!/bin/bash

vendor/bin/sentry-agent &
bin/cake $1
sleep 5
