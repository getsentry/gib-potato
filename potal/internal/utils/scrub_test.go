package utils

import (
	"testing"

	"github.com/google/go-cmp/cmp"
)

func TestScrubText(t *testing.T) {
	tests := []struct {
		input string
		want  string
	}{
		{"", ""},
		{":potato:", ":potato:"},
		{":admission_tickets:", ":admission_tickets:"},
		{"<@U123ABC>", "<@U123ABC>"},
		{"<@U123ABC> :potato: great work!", "<@U123ABC> :potato:"},
		{"hey <@U123ABC> <@U456DEF> :potato::potato: you rock", "<@U123ABC> <@U456DEF> :potato: :potato:"},
		{"just some random text", ""},
		{":potato: :admission_tickets: <@U123ABC>", ":potato: :admission_tickets: <@U123ABC>"},
	}

	for _, test := range tests {
		got := ScrubText(test.input)
		if diff := cmp.Diff(test.want, got); diff != "" {
			t.Errorf("ScrubText(%q) (-want +got):\n%s", test.input, diff)
		}
	}
}
