package utils

import (
	"testing"

	"github.com/google/go-cmp/cmp"
)

func TestReactionReceivers(t *testing.T) {
	type args struct {
		text     string
		itemUser string
	}
	tests := []struct {
		input args
		want  []string
	}{
		{
			input: args{
				text:     "",
				itemUser: "U1111",
			},
			want: []string{"U1111"},
		},
		{
			input: args{
				text:     "<@U1111>",
				itemUser: "U1111",
			},
			want: []string{"U1111"},
		},
		{
			input: args{
				text:     "<@U2222>",
				itemUser: "U1111",
			},
			want: []string{"U2222"},
		},
		{
			input: args{
				text:     "<@U1111><@U2222>",
				itemUser: "U1111",
			},
			want: []string{"U2222"},
		},
		{
			input: args{
				text:     "<@U2222><@U3333>",
				itemUser: "U1111",
			},
			want: []string{"U2222", "U3333"},
		},
	}
	for _, test := range tests {
		got := ReactionReceivers(test.input.text, test.input.itemUser)
		if diff := cmp.Diff(test.want, got); diff != "" {
			t.Errorf("(-want +got):\n%s", diff)
		}
	}
}
