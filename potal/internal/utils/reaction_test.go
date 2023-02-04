package utils

import (
	"testing"

	"github.com/google/go-cmp/cmp"
)

func TestReactionReceivers(t *testing.T) {
	type args struct {
		text     string
		user     string // Person that reacted
		itemUser string // Person that created the message
	}
	tests := []struct {
		input args
		want  []string
	}{
		{
			// No one is mentioned in the message, award the message creator
			input: args{
				text:     "",
				user:     "U2222",
				itemUser: "U1111",
			},
			want: []string{"U1111"},
		},
		{
			// One person is mentioned in the message, award them
			input: args{
				text:     "<@U2222>",
				user:     "U1111",
				itemUser: "U3333",
			},
			want: []string{"U2222"},
		},
		{
			// Multiple people are mentioned in the message, award them
			input: args{
				text:     "<@U2222><@U3333>",
				user:     "U4444",
				itemUser: "U1111",
			},
			want: []string{"U2222", "U3333"},
		},
		{
			// One person is mentioned in the message, the person reacting is them, award the message creator
			input: args{
				text:     "<@U2222>",
				user:     "U2222",
				itemUser: "U1111",
			},
			want: []string{"U1111"},
		},
		{
			// Multiple people are mentioned in the message, the person reacting is one of them, award the others
			input: args{
				text:     "<@U2222><@U3333>",
				user:     "U2222",
				itemUser: "U1111",
			},
			want: []string{"U3333"},
		},
	}
	for _, test := range tests {
		got := ReactionReceivers(test.input.text, test.input.user, test.input.itemUser)
		if diff := cmp.Diff(test.want, got); diff != "" {
			t.Errorf("(-want +got):\n%s", diff)
		}
	}
}
