Call LogEntry()

Sub LogEntry()
	On Error Resume Next
	Dim objRequest
	Dim URL

	URL = "https://www.duckdns.org/update?domains=mumusites&token=ad50a942-3868-4464-8efb-013f638c140e&ip="

	Set objRequest = CreateObject("Microsoft.XMLHTTP")
	objRequest.open "GET", URL , false
	objRequest.Send
	Set objRequest = Nothing
End Sub