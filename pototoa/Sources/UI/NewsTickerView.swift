import SwiftUI

struct NewsTickerView: View {
    @State private var offset: CGFloat = 0
    @State private var contentWidth: CGFloat = 0
    let speed: CGFloat = 50 // points per second

    static let fps: TimeInterval = 60

    let timer = Timer.publish(every: 1.0 / Self.fps, on: .main, in: .common).autoconnect()

    var body: some View {
        GeometryReader { geo in
            HStack(alignment: .center) {
                messagesView
                    .background(GeometryReader { innerGeo in
                        Color.clear
                            .onAppear {
                                contentWidth = innerGeo.size.width
                            }
                    })
                    .id(UUID()) // force re-evaluation when messages change
                // Duplicate for seamless loop
                messagesView
            }
            .offset(x: offset)
        }
        .frame(height: 40)
        .background(Color.black)
        .onReceive(timer) { _ in
            offset -= speed / Self.fps
            if offset < -contentWidth {
                offset += contentWidth
            }
        }
    }

    var messagesView: some View {
        HStack(alignment: .center) {
            ForEach(messages, id: \.self) { message in
                Text(message.uppercased())
                    .font(.system(.body, design: .monospaced))
                    .foregroundColor(.white)
                    .fixedSize()
            }
        }
    }

    let messages: [String] = [
        "🚨 AMS discovers error rate spike – turns out it was daylight saving time.",
        "🧑‍💻 REM engineers accidentally fix a bug by renaming the variable to `fixThisPlease`.",
        "📉 SEA team celebrates 0 errors – forgot to initialize Sentry.",
        "📦 SFO dev pushes to production, blames VIE. VIE blames YYZ.",
        "🤖 YYZ intern adds `print(\"hello\")` – Sentry logs spike by 4000%.",
        "🔁 Sentry detects infinite loop in AMS – team still trying to break out.",
        "🚨 REM deploy labeled “final_final_REALLYfinal_v2” still crashes.",
        "📱 SEA app crash traced to emoji in variable name – 💀",
        "🏆 SFO dev fixes bug in prod – gets promoted instantly.",
        "☕️ VIE engineer sends coffee machine errors to Sentry for observability.",
        "🕵️‍♀️ YYZ team’s error rate drops to zero. Turns out app hasn’t launched yet.",
        "🛑 REM dev accidentally reports Slack downtime to Sentry.",
        "🕒 AMS app logs nothing for 12 hours – turns out it was never running.",
        "🐟 SEA office uses Sentry to monitor who microwaves fish.",
        "🌐 SFO dev adds “AI-powered logging” – logs entire internet.",
        "📳 VIE team sets up Sentry alerts – gets notified 1700 times per minute.",
        "📆 YYZ app crashes only on Tuesdays – Sentry baffled.",
        "😬 REM team renames `catch(error)` to `catch(inevitableFailure)` – error persists.",
        "📚 AMS accidentally sends git history to Sentry – now under investigation.",
        "✅ SEA team disables Sentry in test – finally sees green CI."
    ]
}

struct NewsTickerView_Previews: PreviewProvider {
    static var previews: some View {
        VStack {
            NewsTickerView()
            Spacer()
        }
        .ignoresSafeArea(.all)
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }
}
