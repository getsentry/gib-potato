package utils

func ReactionReceivers(text string, user string, itemUser string) []string {
	receivers := getRecievers(text)

	// If no one was mentioned in the the message text, return the creator of the message
	if len(receivers) == 0 {
		return []string{itemUser}
	}

	// If only one person is mentioned and the person reacting is them, return the creator of the message
	if len(receivers) == 1 {
		if receivers[0] == user {
			return []string{itemUser}
		}
	}

	// If more than one person is mentioned and the person reacting is one of them, remove them
	if len(receivers) > 1 {
		for i, v := range receivers {
			if v == user {
				receivers = append(receivers[:i], receivers[i+1:]...)
			}
		}
	}

	return receivers
}
