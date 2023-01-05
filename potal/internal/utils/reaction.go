package utils

func ReactionReceivers(text string, itemUser string) []string {
	receivers := getRecievers(text)

	// If no one was mentioned in the the message text, return the creator of the message
	if len(receivers) == 0 {
		return []string{itemUser}
	}

	return receivers
}
