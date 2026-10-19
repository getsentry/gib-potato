package utils

import (
	"testing"

	"github.com/google/go-cmp/cmp"
)

func TestGetReceivers(t *testing.T) {
	tests := []struct {
		input string
		want  []string
	}{
		{"", []string{}},
		{"<@U111>", []string{"U111"}},
		{"<@U111><@U222>", []string{"U111", "U222"}},
		{"<@U111><@U111>", []string{"U111"}},
		// Mentions inside inline code or code blocks are not receivers
		{"`npm install <@U111>/pi`", []string{}},
		{"```npm install <@U111>/pi```", []string{}},
		{"```\nnpm install <@U111>/pi\n```", []string{}},
		{"<@U222> try `npm install <@U111>/pi`", []string{"U222"}},
		{"<@U222> try\n```\nnpm install <@U111>/pi\n```\nthanks <@U333>", []string{"U222", "U333"}},
	}

	for _, test := range tests {
		got := getRecievers(test.input)
		if diff := cmp.Diff(test.want, got); diff != "" {
			t.Errorf("(-want +got):\n%s", diff)
		}
	}
}
