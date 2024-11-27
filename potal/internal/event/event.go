package event

type PotalEvent interface {
	isValid() bool
}

type PotalEventType string

const (
	message             PotalEventType = "message"
	directMessage       PotalEventType = "direct_message"
	reactionAdded       PotalEventType = "reaction_added"
	appMention          PotalEventType = "app_mention"
	appHomeOpened       PotalEventType = "app_home_opened"
	slashCommand        PotalEventType = "slash_command"
	interactionCallback PotalEventType = "interaction_callback"
	linkShared          PotalEventType = "link_shared"
)

func (e PotalEventType) String() string {
	return string(e)
}
