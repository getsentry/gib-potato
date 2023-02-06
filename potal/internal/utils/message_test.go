package utils

import (
	"testing"

	"github.com/google/go-cmp/cmp"
)

func TestMessageAmount(t *testing.T) {
	tests := []struct {
		input string
		want  int
	}{
		{"", 0},
		{":potato:", 1},
		{":potato::potato:", 2},
		{":potato::potato::potato:", 3},
		{":potato::potato::potato::potato:", 4},
		{":potato::potato::potato::potato::potato:", 5},
		{":banana:", 0},
		{"potato", 0},
	}

	for _, test := range tests {
		got := MessageAmount(test.input)
		if diff := cmp.Diff(test.want, got); diff != "" {
			t.Errorf("(-want +got):\n%s", diff)
		}
	}
}
