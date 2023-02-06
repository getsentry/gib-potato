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
	}

	for _, test := range tests {
		got := getRecievers(test.input)
		if diff := cmp.Diff(test.want, got); diff != "" {
			t.Errorf("(-want +got):\n%s", diff)
		}
	}
}
